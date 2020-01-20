<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\WriteYaml;
use Typemill\Models\Folder;

class MetaApiController extends ContentController
{
	# get the standard meta-definitions and the meta-definitions from plugins (same for all sites)
	public function getMetaDefinitions(Request $request, Response $response, $args)
	{
		$metatabs = $this->aggregateMetaDefinitions();

		return $response->withJson(array('definitions' => $metatabs, 'errors' => false));
	}

	# get the standard meta-definitions and the meta-definitions from plugins (same for all sites)
	public function aggregateMetaDefinitions()
	{
		$writeYaml = new writeYaml();

		$metatabs = $writeYaml->getYaml('system' . DIRECTORY_SEPARATOR . 'author', 'metatabs.yaml');

		# loop through all plugins
		foreach($this->settings['plugins'] as $name => $plugin)
		{
			if($plugin['active'])
			{
				$pluginSettings = \Typemill\Settings::getObjectSettings('plugins', $name);
				if($pluginSettings && isset($pluginSettings['metatabs']))
				{
					$metatabs = array_merge_recursive($metatabs, $pluginSettings['metatabs']);
				}
			}
		}
		
		# add the meta from theme settings here
		$themeSettings = \Typemill\Settings::getObjectSettings('themes', $this->settings['theme']);
		
		if($themeSettings && isset($themeSettings['metatabs']))
		{
			$metatabs = array_merge_recursive($metatabs, $themeSettings['metatabs']);
		}

		return $metatabs;
	}

	public function getArticleMetaObject(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		$writeYaml = new writeYaml();

		$pagemeta = $writeYaml->getPageMeta($this->settings, $this->item);

		if(!$pagemeta)
		{
			# set the status for published and drafted
			$this->setPublishStatus();
					
			# set path
			$this->setItemPath($this->item->fileType);

			# read content from file
			if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

			$pagemeta = $writeYaml->getPageMetaDefaults($this->content, $this->settings, $this->item);
		}

		# get global metadefinitions
		$metadefinitions = $this->aggregateMetaDefinitions();
		
		$metadata = [];
		$metascheme = [];

		foreach($metadefinitions as $tabname => $tab )
		{
			$metadata[$tabname] 	= [];

			foreach($tab['fields'] as $fieldname => $fielddefinitions)
			{
				$metascheme[$tabname][$fieldname] = true;
				$metadata[$tabname][$fieldname] = isset($pagemeta[$tabname][$fieldname]) ? $pagemeta[$tabname][$fieldname] : null;
			}
		}

		# store the metascheme in cache for frontend
		$writeYaml->updateYaml('cache', 'metatabs.yaml', $metascheme);

		return $response->withJson(array('metadata' => $metadata, 'metadefinitions' => $metadefinitions, 'errors' => false));
	}

	public function updateArticleMeta(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		$tab 			= isset($this->params['tab']) ? $this->params['tab'] : false;
		$metaInput		= isset($this->params['data']) ? $this->params['data'] : false ;
		$objectName		= 'meta';
		$errors 		= false;

		if(!$tab or !$metaInput)
		{
			return $response->withJson($this->errors, 404);
		}

		# load metadefinitions
		$metaDefinitions = $this->aggregateMetaDefinitions();

		# create validation object
		$validate 		= $this->getValidator();

		# take the user input data and iterate over all fields and values
		foreach($metaInput as $fieldName => $fieldValue)
		{
			# get the corresponding field definition from original plugin settings */
			$fieldDefinition = isset($metaDefinitions[$tab]['fields'][$fieldName]) ? $metaDefinitions[$tab]['fields'][$fieldName] : false;

			if(!$fieldDefinition)
			{
				$errors[$tab][$fieldName] = 'This field is not defined';
			}
			else
			{
				# validate user input for this field
				$result = $validate->objectField($fieldName, $fieldValue, $objectName, $fieldDefinition);

				if($result !== true)
				{
					$errors[$tab][$fieldName] = $result[$fieldName][0];
				}
			}
		}

		# return validation errors
		if($errors){ return $response->withJson(array('errors' => $errors),422); }

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
		$writeYaml = new writeYaml();

		# get existing metadata for page
		$metaPage = $writeYaml->getYaml($this->settings['contentFolder'], $this->item->pathWithoutType . '.yaml');
		
		# get extended structure
		$extended = $writeYaml->getYaml('cache', 'structure-extended.yaml');

		# flag for changed structure
		$structure = false;

		if($tab == 'meta')
		{
			# normalize the meta-input
			$metaInput['navtitle'] 	= (isset($metaInput['navtitle']) && $metaInput['navtitle'] !== null )? $metaInput['navtitle'] : '';
			$metaInput['hide'] 		= (isset($metaInput['hide']) && $metaInput['hide'] !== null) ? $metaInput['hide'] : false;

			# input values are empty but entry in structure exists
			if(!$metaInput['hide'] && $metaInput['navtitle'] == "" && isset($extended[$this->item->urlRelWoF]))
			{
				# delete the entry in the structure
				unset($extended[$this->item->urlRelWoF]);

				$structure = true;
			}

			# check if navtitle or hide-value has been changed
			elseif(
				($metaPage['meta']['navtitle'] != $metaInput['navtitle']) 
				OR 
				($metaPage['meta']['hide'] != $metaInput['hide'])
			)
			{
				# add new file data. Also makes sure that the value is set.
				$extended[$this->item->urlRelWoF] = ['hide' => $metaInput['hide'], 'navtitle' => $metaInput['navtitle']];

				$structure = true;
			}

			if($structure)
			{
				# store the file
				$writeYaml->updateYaml('cache', 'structure-extended.yaml', $extended);

				# recreate the draft structure
				$this->setStructure($draft = true, $cache = false);

				# set item in navigation active again
				$activeItem = Folder::getItemForUrl($this->structure, $this->item->urlRel, $this->uri->getBaseUrl());

				# send new structure to frontend
				$structure = $this->structure;
			}
		}

		# add the new/edited metadata
		$meta[$tab] = $metaInput;

		# store the metadata
		$writeYaml->updateYaml($this->settings['contentFolder'], $this->item->pathWithoutType . '.yaml', $meta);

		# return with the new metadata
		return $response->withJson(array('metadata' => $metaInput, 'structure' => $structure, 'errors' => false));
	}
}

# check models -> writeYaml for getPageMeta and getPageMetaDefaults.