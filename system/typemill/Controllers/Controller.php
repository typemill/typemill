<?php

namespace Typemill\Controllers;

use DI\Container;
use Slim\Routing\RouteContext;
use Typemill\Models\StorageWrapper;

# use Psr\Container\ContainerInterface;
# use Typemill\Models\Folder;
# use Typemill\Models\WriteCache;
# use Typemill\Models\WriteYaml;
# use Typemill\Events\OnPageReady;
# use Typemill\Events\OnPagetreeLoaded;
use Typemill\Events\OnTwigLoaded;

abstract class Controller
{
	# holds the container
	protected $c;

	# holds the settings
	protected $settings;

	public function __construct(Container $container)
	{
		$this->c 			= $container;

		$this->routeParser 	= $container->get('routeParser');

		$this->settings 	= $container->get('settings');
	}

	protected function settingActive($setting)
	{
		if(isset($this->settings[$setting]) && $this->settings[$setting])
		{
			return true;
		}

		return false;
	}

	protected function getItem($navigation, $url, $urlinfo)
	{
		$url 				= $this->removeEditorFromUrl($url);
		$langattr 			= $this->settings['langattr'];

		if($url == '/')
		{
			$keyPathArray 		= [''];
		}

		else
		{
			$extendedNavigation	= $navigation->getExtendedNavigation($urlinfo, $langattr);

			$pageinfo 			= $extendedNavigation[$url] ?? false;
			if(!$pageinfo)
			{
				# page not found
				return false;
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

		}

		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
		
		$item				= $navigation->getItemWithKeyPath($draftNavigation, $keyPathArray, $urlinfo['basepath']);

		return $item;
	}		

	protected function removeEditorFromUrl($url)
	{
		$url = trim($url, '/');

		$url = str_replace('tm/content/visual', '', $url);
		$url = str_replace('tm/content/raw', '', $url);

		$url = trim($url, '/');

		return '/' . $url;
	}

	protected function validateRights($userrole, $resource, $action)
	{
		$acl = $this->c->get('acl');

		if($acl->isAllowed($userrole, $resource, $action))
		{
			return true;
		}

		# check ownership.

		$writeMeta = new writeMeta();
		$pagemeta = $writeMeta->getPageMeta($this->settings, $this->item);

		if(
			isset($pagemeta['meta']['owner']) && 
			$pagemeta['meta']['owner'] && 
			$pagemeta['meta']['owner'] !== '' 
		)
		{
			$allowedusers = array_map('trim', explode(",", $pagemeta['meta']['owner']));
			if(
				isset($_SESSION['user']) && 
				in_array($_SESSION['user'], $allowedusers)
			)
			{
				return true;
			}
		}

		return false;
	}

	# move to another place??
	protected function recursiveValidation($validator, array $formdefinitions, $input, $output = [])
	{
		# loop through form-definitions, ignores everything that is not defined in yaml
		foreach($formdefinitions as $fieldname => $fielddefinitions)
		{
			if(is_array($fielddefinitions) && $fielddefinitions['type'] == 'fieldset')
			{
				$output = $this->recursiveValidation($validator, $fielddefinitions['fields'], $input, $output);
			}

			# do not store values for disabled fields
			if(isset($fielddefinitions['disabled']) && $fielddefinitions['type'])
			{
				continue;
			}

			if(isset($input[$fieldname]))
			{
				$fieldvalue = $input[$fieldname];

				$validationresult = $validator->field($fieldname, $fieldvalue, $fielddefinitions);

				if($validationresult === true)
				{
					# MOVE THIS TO A SEPARATE FUNCTION SO YOU CAN STORE IMAGES ONLY IF ALL FIELDS SUCCESSFULLY VALIDATED
					# images have special treatment, check ProcessImage-Model and ImageApiController
					if($fielddefinitions['type'] == 'image')
					{
						# then check if file is there already: check for name and maybe correct image extension (if quality has been changed)
						$storage = new StorageWrapper('\Typemill\Models\Storage');
						$existingImagePath = $storage->checkImage($fieldvalue);
						
						if($existingImagePath)
						{
							$fieldvalue = $existingImagePath;
						}
						else
						{
							# there is no published image with that name, so check if there is an unpublished image in tmp folder and publish it
							$newImagePath = $storage->publishImage($fieldvalue);
							if($newImagePath)
							{
								$fieldvalue = $newImagePath;
							}
							else
							{
								$fieldvalue = '';
							}
						}
					}

					$output[$fieldname] = $fieldvalue;
				}
				else
				{
					$this->errors[$fieldname] = $validationresult[$fieldname][0];
				}
			}
		}

		return $output;
	}
}