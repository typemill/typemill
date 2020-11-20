<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\WriteYaml;
use Typemill\Models\WriteMeta;
use Typemill\Models\Folder;
use Typemill\Events\OnMetaDefinitionsLoaded;

class MetaApiController extends ContentController
{
	# get the standard meta-definitions and the meta-definitions from plugins (same for all sites)
	public function getMetaDefinitions(Request $request, Response $response, $args)
	{
		$metatabs = $this->aggregateMetaDefinitions();

		return $response->withJson(array('definitions' => $metatabs, 'errors' => false));
	}

	# get the standard meta-definitions and the meta-definitions from plugins (same for all sites)
	public function aggregateMetaDefinitions($folder = null)
	{
		$writeYaml = new writeYaml();

		$metatabs = $writeYaml->getYaml('system' . DIRECTORY_SEPARATOR . 'author', 'metatabs.yaml');

		# add radio buttons to choose posts or pages for folder.
		if($folder)
		{
			$metatabs['meta']['fields']['contains'] = [
				'type' 		=> 'radio',
				'label'		=> 'This folder contains:',
				'options' 	=> ['pages' => 'PAGES (sort in navigation with drag & drop)', 'posts' => 'POSTS (sorted by publish date, for news or blogs)'],
				'class'		=> 'medium'
			];
		}

		# loop through all plugins
		if(!empty($this->settings['plugins']))
		{
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
		}
		
		# add the meta from theme settings here
		$themeSettings = \Typemill\Settings::getObjectSettings('themes', $this->settings['theme']);
		
		if($themeSettings && isset($themeSettings['metatabs']))
		{
			$metatabs = array_merge_recursive($metatabs, $themeSettings['metatabs']);
		}

		# dispatch meta 
		$metatabs 		= $this->c->dispatcher->dispatch('onMetaDefinitionsLoaded', new OnMetaDefinitionsLoaded($metatabs))->getData();

		return $metatabs;
	}

	public function getArticleMetaObject(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		$writeMeta = new writeMeta();

		$pagemeta = $writeMeta->getPageMeta($this->settings, $this->item);

		if(!$pagemeta)
		{
			# set the status for published and drafted
			$this->setPublishStatus();

			# set path
			$this->setItemPath($this->item->fileType);

			# read content from file
			if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

			$pagemeta = $writeMeta->getPageMetaBlank($this->content, $this->settings, $this->item);
		}

		# if item is a folder
		if($this->item->elementType == "folder" && isset($this->item->contains))
		{

			$pagemeta['meta']['contains'] = isset($pagemeta['meta']['contains']) ? $pagemeta['meta']['contains'] : $this->item->contains;

			# get global metadefinitions
			$metadefinitions = $this->aggregateMetaDefinitions($folder = true);
		}
		else
		{
			# get global metadefinitions
			$metadefinitions = $this->aggregateMetaDefinitions();
		}
		
		$metadata = [];
		$metascheme = [];

		foreach($metadefinitions as $tabname => $tab )
		{
			$metadata[$tabname] 	= [];

			foreach($tab['fields'] as $fieldname => $fielddefinitions)
			{
				$metascheme[$tabname][$fieldname] = true;
				$metadata[$tabname][$fieldname] = isset($pagemeta[$tabname][$fieldname]) ? $pagemeta[$tabname][$fieldname] : null;

				/*
				# special treatment for customfields
				if(isset($fielddefinitions['type']) && ($fielddefinitions['type'] == 'customfields' ) && $metadata[$tabname][$fieldname] )
				{

					$metadata[$tabname][$fieldname] = $this->customfieldsPrepareForEdit($metadata[$tabname][$fieldname]);

				}
				*/
			}
		}

		# store the metascheme in cache for frontend
		$writeMeta->updateYaml('cache', 'metatabs.yaml', $metascheme);

		return $response->withJson(array('metadata' => $metadata, 'metadefinitions' => $metadefinitions, 'item' => $this->item, 'errors' => false));
	}

	public function updateArticleMeta(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to update content.']), 403);
		}

		$tab 			= isset($this->params['tab']) ? $this->params['tab'] : false;
		$metaInput		= isset($this->params['data']) ? $this->params['data'] : false ;
		$objectName		= 'meta';
		$errors 		= false;

		if(!$tab or !$metaInput)
		{
			return $response->withJson($this->errors, 404);
		}

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to edit content.']), 403);
			}
		}

		# if item is a folder
		if($this->item->elementType == "folder")
		{
			$pagemeta['meta']['contains'] = isset($pagemeta['meta']['contains']) ? $pagemeta['meta']['contains'] : $this->item->contains;

			# get global metadefinitions
			$metaDefinitions = $this->aggregateMetaDefinitions($folder = true);
		}
		else
		{
			# get global metadefinitions
			$metaDefinitions = $this->aggregateMetaDefinitions();
		}

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

				/*
				# special treatment for customfields 
				if($fieldDefinition && isset($fieldDefinition['type']) && ($fieldDefinition['type'] == 'customfields' ) )
				{
					$arrayFeatureOn = false;
					if(isset($fieldDefinition['data']) && ($fieldDefinition['data'] == 'array'))
					{
						$arrayFeatureOn = true;
					}

					$metaInput[$fieldName] = $this->customfieldsPrepareForSave($metaInput[$fieldName], $arrayFeatureOn);
				}
				*/
			}
		}

		# return validation errors
		if($errors){ return $response->withJson(array('errors' => $errors),422); }
		
		$writeMeta = new writeMeta();

		# get existing metadata for page
		$metaPage = $writeMeta->getYaml($this->settings['contentFolder'], $this->item->pathWithoutType . '.yaml');
		
		# get extended structure
		$extended = $writeMeta->getYaml('cache', 'structure-extended.yaml');

		# flag for changed structure
		$structure = false;

		if($tab == 'meta')
		{
			# if manual date has been modified
			if( $this->hasChanged($metaInput, $metaPage['meta'], 'manualdate'))
			{
				# update the time
				$metaInput['time'] = date('H-i-s', time());

				# if it is a post, then rename the post
				if($this->item->elementType == "file" && strlen($this->item->order) == 12)
				{
					# create file-prefix with date
					$datetime 	= $metaInput['manualdate'] . '-' . $metaInput['time'];
					$datetime 	= implode(explode('-', $datetime));
					$datetime	= substr($datetime,0,12);

					# create the new filename
					$pathWithoutFile 	= str_replace($this->item->originalName, "", $this->item->path);
					$newPathWithoutType	= $pathWithoutFile . $datetime . '-' . $this->item->slug;

					$writeMeta->renamePost($this->item->pathWithoutType, $newPathWithoutType);

					# recreate the draft structure
					$this->setStructure($draft = true, $cache = false);

					# update item
					$this->setItem();
				}
			}

			# if folder has changed and contains pages instead of posts or posts instead of pages
			if($this->item->elementType == "folder" && isset($metaInput['contains']) && $this->hasChanged($metaInput, $metaPage['meta'], 'contains'))
			{
				$structure = true;

				if($metaInput['contains'] == "posts")
				{
					$writeMeta->transformPagesToPosts($this->item);
				}
				if($metaInput['contains'] == "pages")
				{
					$writeMeta->transformPostsToPages($this->item);
				}
			}

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
			elseif(
				# check if navtitle or hide-value has been changed
				($this->hasChanged($metaInput, $metaPage['meta'], 'navtitle'))
				OR 
				($this->hasChanged($metaInput, $metaPage['meta'], 'hide'))
			)
			{
				# add new file data. Also makes sure that the value is set.
				$extended[$this->item->urlRelWoF] = ['hide' => $metaInput['hide'], 'navtitle' => $metaInput['navtitle']];

				$structure = true;
			}
		}

		# add the new/edited metadata
		$metaPage[$tab] = $metaInput;

		# store the metadata
		$writeMeta->updateYaml($this->settings['contentFolder'], $this->item->pathWithoutType . '.yaml', $metaPage);

		if($structure)
		{
			# store the extended file
			$writeMeta->updateYaml('cache', 'structure-extended.yaml', $extended);

			# recreate the draft structure
			$this->setStructure($draft = true, $cache = false);

			# update item
			$this->setItem();

			# set item in navigation active again
			$activeItem = Folder::getItemForUrl($this->structure, $this->item->urlRel, $this->uri->getBaseUrl());

			# send new structure to frontend
			$structure = $this->structure;
		}

		# return with the new metadata
		return $response->withJson(array('metadata' => $metaInput, 'structure' => $structure, 'item' => $this->item, 'errors' => false));
	}

	private function customfieldsPrepareForEdit($customfields)
	{
		# to edit fields in vue we have to transform the arrays in yaml into an array of objects like [{key: abc, value: xyz}{...}]

		$customfieldsForEdit = [];

		foreach($customfields as $key => $value)
		{
			$valuestring = $value;

			# and make sure that arrays are transformed back into strings
			if(isset($value) && is_array($value))
			{
				$valuestring = '- ' . implode(PHP_EOL . '- ', $value);
			}

			$customfieldsForEdit[] = ['key' => $key, 'value' => $valuestring];
		}

		return $customfieldsForEdit;
	}

	private function customfieldsPrepareForSave($customfields, $arrayFeatureOn)
	{
		# we have to convert the incoming array of objects from vue [{key: abc, value: xyz}{...}] into key-value arrays for yaml. 

		$customfieldsForSave = [];

		foreach($customfields as $valuePair)
		{
			# doupbe check, not really needed because it is validated already
			if(!isset($valuePair['key']) OR ($valuePair['key'] == ''))
			{
				# do not use data without valid keys
				continue;
			}

			$key 	= $valuePair['key'];
			$value 	= '';

			if(isset($valuePair['value']))
			{
				$value = $valuePair['value'];

				# check if value is formatted as a list, then transform it into an array
				if($arrayFeatureOn)
				{
					# normalize line breaks, php-eol does not work here
					$cleanvalue = str_replace(array("\r\n", "\r"), "\n", $valuePair['value']);
					$cleanvalue = trim($cleanvalue, "\n");

					$arrayValues = explode("\n- ",$cleanvalue);
					
					if(count($arrayValues) > 1)
					{
						$value = array_map(function($item) { return trim($item, '- '); }, $arrayValues);
					}
				}
			}

			$customfieldsForSave[$key] = $value;
		}

		return $customfieldsForSave;
	}

	protected function hasChanged($input, $page, $field)
	{
		if(isset($input[$field]) && isset($page[$field]) && $input[$field] == $page[$field])
		{
			return false;
		}
		if(!isset($input[$field]) && !isset($input[$field]))
		{
			return false;
		}
		return true;
	}
}