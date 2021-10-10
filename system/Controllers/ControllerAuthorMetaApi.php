<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\WriteYaml;
use Typemill\Models\WriteMeta;
use Typemill\Models\Folder;
use Typemill\Events\OnMetaDefinitionsLoaded;

class ControllerAuthorMetaApi extends ControllerAuthor
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

		# the fields for user or role based access
		if(!isset($this->settings['pageaccess']) || $this->settings['pageaccess'] === NULL )
		{
			unset($metatabs['meta']['fields']['fieldsetrights']);
		}

		# add radio buttons to choose posts or pages for folder.
		if(!$folder)
		{
			unset($metatabs['meta']['fields']['contains']);
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
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

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

			$tab = $this->flattenTabFields($tab['fields'],[]);

			foreach($tab as $fieldname => $fielddefinitions)
			{
				$metascheme[$tabname][$fieldname] = true;
				$metadata[$tabname][$fieldname] = isset($pagemeta[$tabname][$fieldname]) ? $pagemeta[$tabname][$fieldname] : null;
				
				# check if there is a selectfield for userroles
				if(isset($fielddefinitions['type']) && ($fielddefinitions['type'] == 'select' ) && isset($fielddefinitions['dataset']) && ($fielddefinitions['dataset'] == 'userroles' ) )
				{
					$userroles = [null => null];
					foreach($this->c->acl->getRoles() as $userrole)
					{
						$userroles[$userrole] = $userrole;
					}

					if(isset($fielddefinitions['fieldset']))
					{
						$metadefinitions[$tabname]['fields'][$fielddefinitions['fieldset']]['fields'][$fieldname]['options'] = $userroles;
					}
					else
					{
						$metadefinitions[$tabname]['fields'][$fieldname]['options'] = $userroles;
					}
				}
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
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

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
		if($this->item->elementType == "folder" && isset($this->item->contains))
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

		$tabFieldDefinitions = $this->flattenTabFields($metaDefinitions[$tab]['fields'], []);

		# create validation object
		$validate 		= $this->getValidator();

		# take the user input data and iterate over all fields and values
		foreach($metaInput as $fieldName => $fieldValue)
		{
			# get the corresponding field definition from original plugin settings */
			$fieldDefinition = isset($tabFieldDefinitions[$fieldName]) ? $tabFieldDefinitions[$fieldName] : false;

			if(!$fieldDefinition)
			{
				$errors[$tab][$fieldName] = 'This field is not defined';
			}
			else
			{
				if($fieldDefinition && isset($fieldDefinition['type']) && ($fieldDefinition['type'] == 'select' ) && isset($fieldDefinition['dataset']) && ($fieldDefinition['dataset'] == 'userroles' ) )
				{
					$userroles = [null => null];
					foreach($this->c->acl->getRoles() as $userrole)
					{
						$userroles[$userrole] = $userrole;
					}
					$fieldDefinition['options'] = $userroles;
				}

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
					$metadate 	= $metaInput['manualdate'];
					if($metadate == ''){ $metadate = $metaPage['meta']['created']; } 
					$datetime 	= $metadate . '-' . $metaInput['time'];
					$datetime 	= implode(explode('-', $datetime));
					$datetime	= substr($datetime,0,12);

					# create the new filename
					$pathWithoutFile 	= str_replace($this->item->originalName, "", $this->item->path);
					$newPathWithoutType	= $pathWithoutFile . $datetime . '-' . $this->item->slug;

					$writeMeta->renamePost($this->item->pathWithoutType, $newPathWithoutType);

					# recreate the draft structure
					$this->setFreshStructureDraft();

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
			$this->setFreshStructureDraft();

			# recreate the live structure
			$this->setFreshStructureLive();

			# recreate the navigation
			$this->setFreshNavigation();

			# update the sitemap
			$this->updateSitemap();			

			# update item
			$this->setItem();

			# set item in navigation active again
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->item->urlRel, $this->uri->getBaseUrl());

			# send new structure to frontend
			$structure = $this->structureDraft;
		}

		# return with the new metadata
		return $response->withJson(array('metadata' => $metaInput, 'structure' => $structure, 'item' => $this->item, 'errors' => false));
	}

	# we have to flatten field definitions for tabs if there are fieldsets in it
	public function flattenTabFields($tabfields, $flattab, $fieldset = null)
	{
		foreach($tabfields as $name => $field)
		{
			if($field['type'] == 'fieldset')
			{
				$flattab = $this->flattenTabFields($field['fields'], $flattab, $name);
			}
			else
			{
				# add the name of the fieldset so we know to which fieldset it belongs for references
				if($fieldset)
				{
					$field['fieldset'] = $fieldset;
				}
				$flattab[$name] = $field;
			}
		}
		return $flattab;
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