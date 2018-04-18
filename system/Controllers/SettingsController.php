<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Field;
use Typemill\Models\Validation;
use Typemill\Models\User;

class SettingsController extends Controller
{
	
	/*********************
	**	BASIC SETTINGS	**
	*********************/
	
	public function showSettings($request, $response, $args)
	{
		$user		= new User();		
		$settings 	= $this->c->get('settings');
		$copyright	= $this->getCopyright();
		$languages	= $this->getLanguages();
		$locale		= explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$locale		= $locale[0];
		$users		= $user->getUsers();
		$route 		= $request->getAttribute('route');
		
		$this->render($response, 'settings/system.twig', array('settings' => $settings, 'copyright' => $copyright, 'languages' => $languages, 'locale' => $locale, 'users' => $users, 'route' => $route->getName() ));
	}
	
	public function saveSettings($request, $response, $args)
	{
		if($request->isPost())
		{
			$settings 		= \Typemill\Settings::getUserSettings();
			$params 		= $request->getParams();
			$newSettings	= isset($params['settings']) ? $params['settings'] : false;
			$validate		= new Validation();
		
			if($newSettings)
			{
				$copyright 					= $this->getCopyright();
				$newSettings['startpage'] 	= isset($newSettings['startpage']) ? true : false;
				
				$validate->settings($newSettings, $copyright, 'settings');
			}
			
			if(isset($_SESSION['errors']))
			{
				$this->c->flash->addMessage('error', 'Please correct the errors');
				return $response->withRedirect($this->c->router->pathFor('settings.show'));
			}
			
			/* store updated settings */
			\Typemill\Settings::updateSettings(array_merge($settings, $newSettings));
			
			$this->c->flash->addMessage('info', 'Settings are stored');
			return $response->withRedirect($this->c->router->pathFor('settings.show'));
		}
	}

	/*********************
	**	THEME SETTINGS	**
	*********************/
	
	public function showThemes($request, $response, $args)
	{
		$settings 	= $this->c->get('settings');
		$themes 	= $this->getThemes();
		$themedata	= array();
			
		foreach($themes as $themeName)
		{	
			/* if theme is active, list it first */
			if($settings['theme'] == $themeName)
			{
				$themedata = array_merge(array($themeName => null), $themedata);
			}
			else
			{
				$themedata[$themeName] = null;
			}
			
			$themeSettings = \Typemill\Settings::getThemeSettings($themeName);
			if($themeSettings)
			{
				/* store them as default theme data with author, year, default settings and field-definitions */
				$themedata[$themeName] = $themeSettings;
			}
			
			if(isset($themeSettings['forms']))
			{
				$fields = array();
				
				/* then iterate through the fields */
				foreach($themeSettings['forms']['fields'] as $fieldName => $fieldConfigs)
				{
					/* and create a new field object with the field name and the field configurations. */
					$field = new Field($fieldName, $fieldConfigs);

					$userValue = false;

					/* add value from stored usersettings */
					if($settings['theme'] == $themeName && isset($settings['themesettings'][$fieldName]))
					{
						$userValue = $settings['themesettings'][$fieldName];
					}
					/* alternatively add value from original theme settings */
					elseif(isset($themeSettings['settings'][$fieldName]))
					{
						$userValue = $themeSettings['settings'][$fieldName];
					}
					/* overwrite it with old input in form, if exists */
					if(isset($_SESSION['old'][$themeName][$fieldName]))
					{
						$userValue = $_SESSION['old'][$themeName][$fieldName];
					}
										
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
								
				/* overwrite original theme form definitions with enhanced form objects */
				$themedata[$themeName]['forms']['fields'] = $fields;				
			}
			
			/* add the preview image */
			$img = getcwd() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . $themeName . '.jpg';
			$img = file_exists($img) ? $img : false;
				
			$themedata[$themeName]['img'] = $img;
		}
		
		/* add the users for navigation */
		$user		= new User();
		$users		= $user->getUsers();
		$route 		= $request->getAttribute('route');
	
		$this->render($response, 'settings/themes.twig', array('settings' => $settings, 'themes' => $themedata, 'users' => $users, 'route' => $route->getName() ));
	}
	
	public function saveThemes($request, $response, $args)
	{
		if($request->isPost())
		{
			$settings 		= \Typemill\Settings::getUserSettings();
			$params 		= $request->getParams();
			$theme			= isset($params['theme']) ? $params['theme'] : false;
			$themeSettings	= isset($params[$theme]) ? $params[$theme] : false;
			$validate		= new Validation();
			
			/* set theme name and delete theme settings from user settings for the case, that the new theme has no settings */

			$settings['theme'] = $theme;
			unset($settings['themesettings']);
						
			if($themeSettings)
			{
				// load theme definitions by theme name
				$themeOriginalSettings = \Typemill\Settings::getThemeSettings($theme);

				// validate input with field definitions				
				if($themeOriginalSettings)
				{
					foreach($themeSettings as $fieldName => $fieldValue)
					{
						$fieldDefinition = isset($themeOriginalSettings['forms']['fields'][$fieldName]) ? $themeOriginalSettings['forms']['fields'][$fieldName] : false;
						if($fieldDefinition)
						{
							/* validate user input for this field */
							$validate->pluginField($fieldName, $fieldValue, $theme, $fieldDefinition);
						}
						else
						{
							$_SESSION['errors'][$themeName][$fieldName] = 'This field is not defined for the theme!';
						}
					}
				}
				$settings['themesettings'] 	= $themeSettings;
			}
						
			/* check for errors and redirect to path, if errors found */
			if(isset($_SESSION['errors']))
			{
				$this->c->flash->addMessage('error', 'Please correct the errors');
				return $response->withRedirect($this->c->router->pathFor('themes.show'));
			}

			/* store updated settings */
			\Typemill\Settings::updateSettings($settings);
			
			$this->c->flash->addMessage('info', 'Settings are stored');
			return $response->withRedirect($this->c->router->pathFor('themes.show'));
		}
	}


	/*********************
	**	Plugin SETTINGS	**
	*********************/
	
	public function showPlugins($request, $response, $args)
	{
		$settings 	= $this->c->get('settings');
		$plugins	= array();
		$fields 	= array();

		/* iterate through the plugins in the stored user settings */
		foreach($settings['plugins'] as $pluginName => $pluginUserSettings)
		{
			/* add plugin to plugin Data, if active, set it first */
			/* if theme is active, list it first */
			if($settings['plugins'][$pluginName]['active'] == true)
			{
				$plugins = array_merge(array($pluginName => null), $plugins);
			}
			else
			{
				$plugins[$pluginName] = Null;
			}
						
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
		
		$user 	= new User();
		$users 	= $user->getUsers();
		$route 	= $request->getAttribute('route');
		
		$this->render($response, 'settings/plugins.twig', array('settings' => $settings, 'plugins' => $plugins, 'users' => $users, 'route' => $route->getName() ));
	}

	public function savePlugins($request, $response, $args)
	{
		if($request->isPost())
		{
			$settings 		= \Typemill\Settings::getUserSettings();
			$pluginSettings	= array();
			$params 		= $request->getParams();
			$validate		= new Validation();
					
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
					$pluginOriginalSettings = \Typemill\Settings::getPluginSettings($pluginName);
					
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
							if(!$fieldDefinition && $fieldName != 'active')
							{
								$_SESSION['errors'][$pluginName][$fieldName] = 'This field is not defined in the plugin!';
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
						
			if(isset($_SESSION['errors']))
			{
				$this->c->flash->addMessage('error', 'Please correct the errors below');
			}
			else
			{
				/* if everything is valid, add plugin settings to base settings again */
				$settings['plugins'] = $pluginSettings;
				
				/* store updated settings */
				\Typemill\Settings::updateSettings($settings);

				$this->c->flash->addMessage('info', 'Settings are stored');
			}
			
			return $response->withRedirect($this->c->router->pathFor('plugins.show'));
		}
	}

	
	/***********************
	**   USER MANAGEMENT  **
	***********************/
	
	public function showUser($request, $response, $args)
	{
		$validate 	= new Validation();
		
		if($validate->username($args['username']))
		{
			$user 		= new User();
			$users		= $user->getUsers();
			$userrole	= $user->getUserroles();
			$userdata 	= $user->getUser($args['username']);
			
			if($userdata)
			{				
				return $this->render($response, 'settings/user.twig', array('users' => $users, 'userdata' => $userdata, 'userrole' => $userrole, 'username' => $args['username'] ));
			}
		}
		
		$this->c->flash->addMessage('error', 'User does not exists');
		return $response->withRedirect($this->c->router->pathFor('user.list'));
	}

	public function listUser($request, $response)
	{
		$user		= new User();
		$users		= $user->getUsers();
		$userdata 	= array();
		$route 		= $request->getAttribute('route');
		
		foreach($users as $username)
		{
			$userdata[] = $user->getUser($username);
		}
		
		$this->render($response, 'settings/userlist.twig', array('users' => $users, 'userdata' => $userdata, 'route' => $route->getName() ));		
	}
	
	public function newUser($request, $response, $args)
	{
		$user 		= new User();
		$users		= $user->getUsers();
		$userrole	= $user->getUserroles();
		$route 		= $request->getAttribute('route');

		$this->render($response, 'settings/usernew.twig', array('users' => $users, 'userrole' => $userrole, 'route' => $route->getName() ));
	}
		
	public function createUser($request, $response, $args)
	{
		if($request->isPost())
		{
			$params 		= $request->getParams();
			$user 			= new User();
			$userroles		= $user->getUserroles();
			$validate		= new Validation();

			if($validate->newUser($params, $userroles))
			{
				$userdata	= array('username' => $params['username'], 'email' => $params['email'], 'userrole' => $params['userrole'], 'password' => $params['password']);
				
				$user->createUser($userdata);

				$this->c->flash->addMessage('info', 'Welcome, there is a new user!');
				return $response->withRedirect($this->c->router->pathFor('user.list'));
			}
			
			$this->c->flash->addMessage('error', 'Please correct your input');
			return $response->withRedirect($this->c->router->pathFor('user.new'));
		}
	}
	
	public function updateUser($request, $response, $args)
	{
		if($request->isPost())
		{
			$params 		= $request->getParams();
			$user 			= new User();
			$userroles		= $user->getUserroles();
			$validate		= new Validation();

			if($validate->existingUser($params, $userroles))
			{
				$userdata	= array('username' => $params['username'], 'email' => $params['email'], 'userrole' => $params['userrole']);
				
				if(empty($params['password']) AND empty($params['newpassword']))
				{
					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($this->c->router->pathFor('user.list'));
				}
				elseif($validate->newPassword($params))
				{
					$userdata['password'] = $params['newpassword'];				
					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($this->c->router->pathFor('user.list'));
				}
			}
			
			$this->c->flash->addMessage('error', 'Please correct your input');
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));
		}
	}
	
	public function deleteUser($request, $response, $args)
	{
		if($request->isPost())
		{
			$params 		= $request->getParams();
			$validate		= new Validation();
			$user			= new User();
			
			if($validate->username($params['username']))
			{
				$user->deleteUser($params['username']);
				
				$this->c->flash->addMessage('info', 'Say goodbye, the user is gone!');
				return $response->withRedirect($this->c->router->pathFor('user.list'));			
			}
			
			$this->c->flash->addMessage('error', 'Ups, we did not find that user');
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));			
		}
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
	
	private function getLanguages()
	{
		return array(
			'ab' => 'Abkhazian',
			'aa' => 'Afar',
			'af' => 'Afrikaans',
			'ak' => 'Akan',
			'sq' => 'Albanian',
			'am' => 'Amharic',
			'ar' => 'Arabic',
			'an' => 'Aragonese',
			'hy' => 'Armenian',
			'as' => 'Assamese',
			'av' => 'Avaric',
			'ae' => 'Avestan',
			'ay' => 'Aymara',
			'az' => 'Azerbaijani',
			'bm' => 'Bambara',
			'ba' => 'Bashkir',
			'eu' => 'Basque',
			'be' => 'Belarusian',
			'bn' => 'Bengali',
			'bh' => 'Bihari languages',
			'bi' => 'Bislama',
			'bs' => 'Bosnian',
			'br' => 'Breton',
			'bg' => 'Bulgarian',
			'my' => 'Burmese',
			'ca' => 'Catalan, Valencian',
			'km' => 'Central Khmer',
			'ch' => 'Chamorro',
			'ce' => 'Chechen',
			'ny' => 'Chichewa, Chewa, Nyanja',
			'zh' => 'Chinese',
			'cu' => 'Church Slavonic, Old Bulgarian, Old Church Slavonic',
			'cv' => 'Chuvash',
			'kw' => 'Cornish',
			'co' => 'Corsican',
			'cr' => 'Cree',
			'hr' => 'Croatian',
			'cs' => 'Czech',
			'da' => 'Danish',
			'dv' => 'Divehi, Dhivehi, Maldivian',
			'nl' => 'Dutch, Flemish',
			'dz' => 'Dzongkha',
			'en' => 'English',
			'eo' => 'Esperanto',
			'et' => 'Estonian',
			'ee' => 'Ewe',
			'fo' => 'Faroese',
			'fj' => 'Fijian',
			'fi' => 'Finnish',
			'fr' => 'French',
			'ff' => 'Fulah',
			'gd' => 'Gaelic, Scottish Gaelic',
			'gl' => 'Galician',
			'lg' => 'Ganda',
			'ka' => 'Georgian',
			'de' => 'German',
			'ki' => 'Gikuyu, Kikuyu',
			'el' => 'Greek (Modern)',
			'kl' => 'Greenlandic, Kalaallisut',
			'gn' => 'Guarani',
			'gu' => 'Gujarati',
			'ht' => 'Haitian, Haitian Creole',
			'ha' => 'Hausa',
			'he' => 'Hebrew',
			'hz' => 'Herero',
			'hi' => 'Hindi',
			'ho' => 'Hiri Motu',
			'hu' => 'Hungarian',
			'is' => 'Icelandic',
			'io' => 'Ido',
			'ig' => 'Igbo',
			'id' => 'Indonesian',
			'ia' => 'Interlingua (International Auxiliary Language Association)',
			'ie' => 'Interlingue',
			'iu' => 'Inuktitut',
			'ik' => 'Inupiaq',
			'ga' => 'Irish',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'jv' => 'Javanese',
			'kn' => 'Kannada',
			'kr' => 'Kanuri',
			'ks' => 'Kashmiri',
			'kk' => 'Kazakh',
			'rw' => 'Kinyarwanda',
			'kv' => 'Komi',
			'kg' => 'Kongo',
			'ko' => 'Korean',
			'kj' => 'Kwanyama, Kuanyama',
			'ku' => 'Kurdish',
			'ky' => 'Kyrgyz',
			'lo' => 'Lao',
			'la' => 'Latin',
			'lv' => 'Latvian',
			'lb' => 'Letzeburgesch, Luxembourgish',
			'li' => 'Limburgish, Limburgan, Limburger',
			'ln' => 'Lingala',
			'lt' => 'Lithuanian',
			'lu' => 'Luba-Katanga',
			'mk' => 'Macedonian',
			'mg' => 'Malagasy',
			'ms' => 'Malay',
			'ml' => 'Malayalam',
			'mt' => 'Maltese',
			'gv' => 'Manx',
			'mi' => 'Maori',
			'mr' => 'Marathi',
			'mh' => 'Marshallese',
			'ro' => 'Moldovan, Moldavian, Romanian',
			'mn' => 'Mongolian',
			'na' => 'Nauru',
			'nv' => 'Navajo, Navaho',
			'nd' => 'Northern Ndebele',
			'ng' => 'Ndonga',
			'ne' => 'Nepali',
			'se' => 'Northern Sami',
			'no' => 'Norwegian',
			'nb' => 'Norwegian Bokmål',
			'nn' => 'Norwegian Nynorsk',
			'ii' => 'Nuosu, Sichuan Yi',
			'oc' => 'Occitan (post 1500)',
			'oj' => 'Ojibwa',
			'or' => 'Oriya',
			'om' => 'Oromo',
			'os' => 'Ossetian, Ossetic',
			'pi' => 'Pali',
			'pa' => 'Panjabi, Punjabi',
			'ps' => 'Pashto, Pushto',
			'fa' => 'Persian',
			'pl' => 'Polish',
			'pt' => 'Portuguese',
			'qu' => 'Quechua',
			'rm' => 'Romansh',
			'rn' => 'Rundi',
			'ru' => 'Russian',
			'sm' => 'Samoan',
			'sg' => 'Sango',
			'sa' => 'Sanskrit',
			'sc' => 'Sardinian',
			'sr' => 'Serbian',
			'sn' => 'Shona',
			'sd' => 'Sindhi',
			'si' => 'Sinhala, Sinhalese',
			'sk' => 'Slovak',
			'sl' => 'Slovenian',
			'so' => 'Somali',
			'st' => 'Sotho, Southern',
			'nr' => 'South Ndebele',
			'es' => 'Spanish, Castilian',
			'su' => 'Sundanese',
			'sw' => 'Swahili',
			'ss' => 'Swati',
			'sv' => 'Swedish',
			'tl' => 'Tagalog',
			'ty' => 'Tahitian',
			'tg' => 'Tajik',
			'ta' => 'Tamil',
			'tt' => 'Tatar',
			'te' => 'Telugu',
			'th' => 'Thai',
			'bo' => 'Tibetan',
			'ti' => 'Tigrinya',
			'to' => 'Tonga (Tonga Islands)',
			'ts' => 'Tsonga',
			'tn' => 'Tswana',
			'tr' => 'Turkish',
			'tk' => 'Turkmen',
			'tw' => 'Twi',
			'ug' => 'Uighur, Uyghur',
			'uk' => 'Ukrainian',
			'ur' => 'Urdu',
			'uz' => 'Uzbek',
			've' => 'Venda',
			'vi' => 'Vietnamese',
			'vo' => 'Volap_k',
			'wa' => 'Walloon',
			'cy' => 'Welsh',
			'fy' => 'Western Frisian',
			'wo' => 'Wolof',
			'xh' => 'Xhosa',
			'yi' => 'Yiddish',
			'yo' => 'Yoruba',
			'za' => 'Zhuang, Chuang',
			'zu' => 'Zulu'
		);
	}
}