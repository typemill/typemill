<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Field;
use Typemill\Models\Validation;

class SetupController extends Controller
{
	public function setup($request, $response, $args)
	{
		$settings 	= $this->c->get('settings');
		$themes 	= $this->getThemes();
		$copyright	= $this->getCopyright();
		
		$plugins	= array();
		$fields 	= array();
		
		/* iterate through the plugins in the stored user settings */
		foreach($settings['plugins'] as $pluginName => $pluginUserSettings)
		{
			/* add plugin to plugin Data */
			$plugins[$pluginName] = Null;
			
			/* Check if the user has deleted a plugin. Then delete it in the settings and store the updated settings. */
			if(!is_dir($settings['rootPath'] . 'plugins' . DIRECTORY_SEPARATOR . $pluginName))
			{
				/* remove the plugin settings and store updated settings */
				\Typemill\Settings::removePluginSettings($pluginName);
				continue;
			}

			/* load the original plugin definitions from the plugin folder (author, version and stuff) */
			$pluginOriginalSettings = \Typemill\Settings::getPluginSettings($pluginName);
			if($pluginOriginalSettings)
			{
				/* store them as default plugin data with plugin author, plugin year, default settings and field-definitions */
				$plugins[$pluginName] = $pluginOriginalSettings;
			}
						
			/* overwrite the original plugin settings with the stored user settings, if they exist */
			if($pluginUserSettings)
			{
				$plugins[$pluginName]['settings'] = $pluginUserSettings;
			}
			
			/* check, if the plugin has been disabled in the form-session-data */
			/* TODO: Works only, if there is at least one plugin with settings */
			if(isset($_SESSION['old']) && !isset($_SESSION['old'][$pluginName]['active']))
			{
				$plugins[$pluginName]['settings']['active'] = false;
			}
			
			/* if the plugin defines forms and fields, so that the user can edit the plugin settings in the frontend */
			if(isset($pluginOriginalSettings['forms']))
			{
				$fields = array();
				
				/* then iterate through the fields */
				foreach($pluginOriginalSettings['forms']['fields'] as $fieldName => $fieldConfigs)
				{
					/* and create a new field object with the field name and the field configurations. */
					$field = new Field($fieldName, $fieldConfigs);
					
					/* now you have the configurations of the field. Time to set the values */
					
					/* At first, get the value for the field from the stored user settings */
					// $userValue = isset($pluginUserSettings[$fieldName]) ? $pluginUserSettings[$fieldName] : NULL;
					$userValue = isset($plugins[$pluginName]['settings'][$fieldName]) ? $plugins[$pluginName]['settings'][$fieldName] : NULL;
					
					/* Then overwrite the value, if there are old input values for the field in the session */
					$userValue = isset($_SESSION['old'][$pluginName][$fieldName]) ? $_SESSION['old'][$pluginName][$fieldName] : $userValue;
					
					if($field->getType() == "textarea")
					{
						if($userValue)
						{
							$field->setContent($userValue);
						}
					}
					elseIf($field->getType() != "checkbox")
					{
						$field->setAttributeValue('value', $userValue);	
					}

					/* add the field to the field-List with the plugin-name as key */
					$fields[] = $field;
				}
				/* overwrite original plugin form definitions with enhanced form objects */
				$plugins[$pluginName]['forms']['fields'] = $fields;
			}
		}
		$this->c->view->render($response, '/setup.twig', array('settings' => $settings, 'themes' => $themes,'copyright' => $copyright,'plugins' => $plugins));
	}

	public function save($request, $response, $args)
	{
		if($request->isPost())
		{
			$settings 		= $this->c->get('settings');
			$pluginSettings	= array();
			$params 		= $request->getParams();
			$validate		= new Validation();
		
			/* extract the settings for the basic application and validate them */
			$appSettings	= isset($params['settings']) ? $params['settings'] : false;
			if($appSettings)
			{
				$copyright 					= $this->getCopyright();
				$themes 					= $this->getThemes();
				$appSettings['startpage'] 	= isset($appSettings['startpage']) ? true : false;
				
				$validate->settings($appSettings, $themes, $copyright, 'settings');
			}
			
			/* use the stored user settings and iterate over all original plugin settings, so we do not forget any... */
			foreach($settings['plugins'] as $pluginName => $pluginUserSettings)
			{
				/* if there are no input-data for this plugin, then use the stored plugin settings */
				if(!isset($params[$pluginName]))
				{
					$pluginSettings[$pluginName] = $pluginUserSettings;
				}
				else
				{					
					/* now fetch the original plugin settings from the plugin folder to get the field definitions */
					$pluginOriginalSettings = \Typemill\settings::getPluginSettings($pluginName);
					
					if($pluginOriginalSettings)
					{
						/* take the user input data and iterate over all fields and values */
						foreach($params[$pluginName] as $fieldName => $fieldValue)
						{
							/* get the corresponding field definition from original plugin settings */
							$fieldDefinition = isset($pluginOriginalSettings['forms']['fields'][$fieldName]) ? $pluginOriginalSettings['forms']['fields'][$fieldName] : false;
							if($fieldDefinition)
							{
								/* validate user input for this field */
								$validate->pluginField($fieldName, $fieldValue, $pluginName, $fieldDefinition);
							}
						}
					}
					
					/* use the input data */
					$pluginSettings[$pluginName] = $params[$pluginName];
				}
				
				/* deactivate the plugin, if there is no active flag */					
				if(!isset($params[$pluginName]['active']))
				{
					$pluginSettings[$pluginName]['active'] = false;
				}
			}
			
			if(!is_writable($this->c->get('settings')['settingsPath']))
			{
				$_SESSION['errors']['folder'] = 'Your settings-folder is not writable';
			}

			if(isset($_SESSION['errors']))
			{
				return $response->withRedirect($this->c->router->pathFor('setup'));
			}
			
			/* if everything is valid, add plugin settings to base settings again */
			$appSettings['plugins'] = $pluginSettings;
						
			/* store updated settings */			
			\Typemill\Settings::updateSettings($appSettings);
			
			unset($_SESSION['old']);
			
			$this->c->view->render($response, '/welcome.twig', $appSettings);
		}
	}
		
	private function getCopyright()
	{
		return array(
			"©",
			"CC-BY",
			"CC-BY-NC",
			"CC-BY-NC-ND",
			"CC-BY-NC-SA",
			"CC-BY-ND",
			"CC-BY-SA",
			"None"
		);
	}
	
	private function getThemes()
	{
		$themeFolder 	= $this->c->get('settings')['rootPath'] . $this->c->get('settings')['themeFolder'];
		$themeFolderC 	= scandir($themeFolder);
		$themes 		= array();
		foreach ($themeFolderC as $key => $theme)
		{
			if (!in_array($theme, array(".","..")))
			{
				if (is_dir($themeFolder . DIRECTORY_SEPARATOR . $theme))
				{
					$themes[] = $theme;
				}
			}
		}
		return $themes;
	}
}