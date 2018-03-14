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
		$languages	= $this->getLanguages();
		$locale		= explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$locale		= $locale[0];
		
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
		$this->c->view->render($response, '/setup.twig', array('settings' => $settings, 'themes' => $themes,'copyright' => $copyright,'plugins' => $plugins, 'languages' => $languages, 'locale' => $locale));
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
			
			$themeSettings	= isset($params['themesettings']) ? $params['themesettings'] : false;
			if($themeSettings)
			{
				// load theme definitions by theme name
				// validate input with field definitions
				$appSettings['themesettings'] = $themeSettings;
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

	public function themes($request, $response)
	{
		/* Extract the parameters from get-call */
		$params 	= $request->getParams();
		$theme	 	= isset($params['theme']) ? $params['theme'] : false;

		if($theme && preg_match('/^[A-Za-z0-9 _\-\+]+$/', $theme))
		{
			$themeSettings = \Typemill\Settings::getThemeSettings($theme);
			
			if($themeSettings)
			{
				return $response->withJson($themeSettings, 200);
			}
		}
		
		return $response->withJson(['error' => 'no data found'], 404);		
	}
}