<?php

namespace Typemill\Controllers;

use Typemill\Models\Yaml;
use Typemill\Events\OnSystemnaviLoaded;

# this controller handels data for web and api
# web will use data for twig output
# api will use data for json output
# data controller will provide neutral data

class ControllerData extends Controller
{
	protected $errors = [];

	protected function getMainNavigation($userrole)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');

		$mainnavi 		= $yaml->getYaml('system/typemill/settings', 'mainnavi.yaml');

		$allowedmainnavi = [];

		$acl 			= $this->c->get('acl');

		foreach($mainnavi as $name => $naviitem)
		{
			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				# not nice: check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
				if($name == 'content' && strpos($this->c->get('urlinfo')['route'], 'tm/content'))
				{
					$naviitem['active'] = true;
				}
				elseif($name == 'account' && strpos($this->c->get('urlinfo')['route'], 'tm/account'))
				{
					$naviitem['active'] = true;
				}
				elseif($name == 'system')
				{
					$naviitem['active'] = true;
				}

				$allowedmainnavi[$name] = $naviitem;
			}
		}

		# if system is there, then we do not need the account item
		if(isset($allowedmainnavi['system']))
		{
			unset($allowedmainnavi['account']);
		}

		# set correct editor mode according to user settings
		if(isset($allowedmainnavi['content']) && $this->settings['editor'] == 'raw')
		{
			$allowedmainnavi['content']['routename'] = "content.raw";
		}

		return $allowedmainnavi;
	}

	protected function getSystemNavigation($userrole)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');

		$systemnavi 	= $yaml->getYaml('system/typemill/settings', 'systemnavi.yaml');
		$systemnavi 	= $this->c->get('dispatcher')->dispatch(new OnSystemnaviLoaded($systemnavi), 'onSystemnaviLoaded')->getData();

		$allowedsystemnavi = [];

		$acl 			= $this->c->get('acl');

		foreach($systemnavi as $name => $naviitem)
		{
			# check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
			# a bit fragile because url-segment and name/key in systemnavi.yaml and plugins have to be the same
			if(strpos($this->c->get('urlinfo')['route'], 'tm/' . $name))
			{
				$naviitem['active'] = true;
			}

			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				$allowedsystemnavi[$name] = $naviitem;
			}
		}

		return $allowedsystemnavi;
	}

	protected function getThemeDetails()
	{
		$themes = $this->getThemes();

		$themeDetails = [];
		foreach($themes as $themeName)
		{
			$themeDetails[$themeName] = $this->getThemeDefinition($themeName);
		}

		return $themeDetails;
	}

	protected function getThemes()
	{
		$themeFolder 	= $this->c->get('settings')['rootPath'] . DIRECTORY_SEPARATOR . $this->c->get('settings')['themeFolder'];
		$themeFolderC 	= scandir($themeFolder);
		$themes 		= [];
		foreach ($themeFolderC as $key => $theme)
		{
			if (!in_array($theme, [".",".."]))
			{
				if (is_dir($themeFolder . DIRECTORY_SEPARATOR . $theme))
				{
					$themes[] = $theme;
				}
			}
		}

		return $themes;
	}

	protected function getThemeDefinition($themeName)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');

		$themeSettings 	= $yaml->getYaml('themes' . DIRECTORY_SEPARATOR . $themeName, $themeName . '.yaml');

		# add standard-textarea for custom css
		$themeSettings['forms']['fields']['customcss'] = [
			'type' 			=> 'textarea', 
			'label' 		=> 'Custom CSS', 
			'rows' 			=> 10, 
			'class' 		=> 'codearea', 
			'description' 	=> 'You can overwrite the theme-css with your own css here.'
		];

		# add image preview file 
		$themeSettings['preview'] = 'http://localhost/typemill/themes/' . $themeName . '/' . $themeName . '.png';

		return $themeSettings;
	}

	protected function getPluginDetails()
	{
		$plugins = $this->getPlugins();

		$pluginDetails = [];
		foreach($plugins as $pluginName)
		{
			$pluginDetails[$pluginName] = $this->getPluginDefinition($pluginName);
		}

		return $pluginDetails;
	}

	protected function getPlugins()
	{
		$pluginFolder 	= $this->c->get('settings')['rootPath'] . DIRECTORY_SEPARATOR . $this->c->get('settings')['pluginFolder'];
		$pluginFolderC 	= scandir($pluginFolder);
		$plugins 		= [];
		foreach ($pluginFolderC as $key => $plugin)
		{
			if (!in_array($plugin, [".",".."]))
			{
				if (is_dir($pluginFolder . DIRECTORY_SEPARATOR . $plugin))
				{
					$plugins[] = $plugin;
				}
			}
		}

		return $plugins;
	}

	protected function getPluginDefinition($pluginName)
	{
		$yaml 				= new Yaml('\Typemill\Models\Storage');

		$pluginSettings 	= $yaml->getYaml('plugins' . DIRECTORY_SEPARATOR . $pluginName, $pluginName . '.yaml');

		return $pluginSettings;
	}

	protected function getUserFields($userrole,$inspectorrole = NULL)
	{
		if(!$inspectorrole)
		{
			# if there is no inspector-role we assume that it is the same role like the userrole 
			# for example account is always visible by the same user
			# edit user can be done by another user like admin.
			$inspectorrole = $userrole;
		}

		$yaml 			= new Yaml('\Typemill\Models\Storage');

		$userfields 	= $yaml->getYaml('system/typemill/settings', 'user.yaml');

		# if a plugin with a role has been deactivated, then users with the role throw an error, so set them back to member...
		if(!$this->c->get('acl')->hasRole($userrole))
		{
			$userrole = 'member';
		}

		# dispatch fields;
		#$fields = $this->c->dispatcher->dispatch('onUserfieldsLoaded', new OnUserfieldsLoaded($fields))->getData();

		# only roles who can edit content need profile image and description
		if($this->c->get('acl')->isAllowed($userrole, 'mycontent', 'create'))
		{
			$newfield['image'] 			= ['label' => 'Profile-Image', 'type' => 'image'];
			$newfield['description'] 	= ['label' => 'Author-Description (Markdown)', 'type' => 'textarea'];
			
			$userfields = array_slice($userfields, 0, 1, true) + $newfield + array_slice($userfields, 1, NULL, true);
			# array_splice($fields,1,0,$newfield);
		}

		# Only admin can change userroles
		if($this->c->get('acl')->isAllowed($inspectorrole, 'userlist', 'write'))
		{
			$definedroles = $this->c->get('acl')->getRoles();
			$options = [];

			# we need associative array to make select-field with key/value work
			foreach($definedroles as $role)
			{
				$options[$role] = $role;
 			}

			$userfields['userrole'] = ['label' => 'Role', 'type' => 'select', 'options' => $options];
		}

		return $userfields;
	}

	protected function recursiveValidation($formdefinitions, $input, $validator, $themeOrPlugin = false, $name = false)
	{
		# loop through form-definitions, ignores everything that is not defined in yaml
		foreach($formdefinitions as $fieldname => $fielddefinitions)
		{
			if(is_array($fielddefinitions) && $fielddefinitions['type'] == 'fieldset')
			{
				$this->recursiveValidation($fielddefinitions['fields'], $input, $validator, $themeOrPlugin, $name);
			}

			$fieldvalue = isset($input[$fieldname]) ? $input[$fieldname] : false;

			if($fieldvalue)
			{
				$validationresult = $validator->field($fieldname, $fieldvalue, $fielddefinitions);

				if($validationresult === true)
				{
					# if input is valid, overwrite value in original settings
					if($themeOrPlugin)
					{
						$this->settings[$themeOrPlugin][$name][$fieldname] = $fieldvalue;
					}
					else
					{
						$this->settings[$fieldname] = $fieldvalue;
					}
				}
				else
				{
					$this->errors[$fieldname] = $validationresult[$fieldname][0];
				}
			}
		}
	}	
}