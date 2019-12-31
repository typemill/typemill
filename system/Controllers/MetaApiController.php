<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\WriteYaml;

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

		# load cached metadefinitions
		# check if valid
		# if not, refresh cache

		# loop through all plugins
		foreach($this->settings['plugins'] as $name => $plugin)
		{
			$pluginSettings = \Typemill\Settings::getObjectSettings('plugins', $name);
			if($pluginSettings && isset($pluginSettings['metatabs']))
			{
				$metatabs = array_merge_recursive($metatabs, $pluginSettings['metatabs']);
			}
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

		foreach($metadefinitions as $tabname => $tab )
		{
			$metadata[$tabname] = [];

			foreach($tab['fields'] as $fieldname => $fielddefinitions)
			{
				$metadata[$tabname][$fieldname] = isset($pagemeta[$tabname][$fieldname]) ? $pagemeta[$tabname][$fieldname] : null;
			}
		}

		return $response->withJson(array('metadata' => $metadata, 'metadefinitions' => $metadefinitions, 'errors' => false));
	}

	public function updateArticleMeta(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		$tab 			= isset($this->params['tab']) ? $this->params['tab'] : false;
		$metaData		= isset($this->params['data']) ? $this->params['data'] : false ;
		$objectName		= 'meta';
		$errors 		= false;

		if(!$tab or !$metaData)
		{
			return $response->withJson($this->errors, 404);
		}

		# load metadefinitions
		$metaDefinitions = $this->aggregateMetaDefinitions();

		# create validation object
		$validate 		= $this->getValidator();

		# take the user input data and iterate over all fields and values
		foreach($metaData as $fieldName => $fieldValue)
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
		$meta = $writeYaml->getYaml($this->settings['contentFolder'], $this->item->pathWithoutType . '.yaml');

		# add the new/edited metadata
		$meta[$tab] = $metaData;

		# store the metadata
		$writeYaml->updateYaml($this->settings['contentFolder'], $this->item->pathWithoutType . '.yaml', $meta);

		# return with the new metadata
		return $response->withJson(array('metadata' => $metaData, 'errors' => false));
	}
}