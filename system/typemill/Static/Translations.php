<?php 

namespace Typemill\Static;

use Typemill\Models\StorageWrapper;

class Translations
{
	public static function loadTranslations($settings, $route)
	{
		$storage = new StorageWrapper($settings['storage']);

		$urlsegments = explode('/',trim($route,'/'));
	
		$environment = 'frontend';
		if( ($urlsegments[0] === 'tm' OR $urlsegments[0] === 'setup') )
		{
			$environment = 'admin';
		}

		$language = self::whichLanguage();
		if(isset($settings['language']))
		{
		  	$language = $settings['language'];
		}

		$theme_translations 	= [];
		$system_translations 	= [];
		$plugins_translations 	= [];

		# theme labels selected according to the environment: admin or user
		$theme_translations 	= $storage->getYaml('themeFolder', $settings['theme'], $language . '.yaml') ?? [];

		if($environment == 'admin')
		{
			$system_translations 		= $storage->getYaml('translationFolder', '', $language . '.yaml');

			# Next change, to provide labels for the admin and user environments.
			# There may be plugins that only work in the user environment, only in the admin environment, or in both environments.
	  		$plugin_labels = [];
	  		if(isset($settings['plugins']) && !empty($settings['plugins']))
	  		{
			  	foreach($settings['plugins'] as $plugin => $config)
			  	{
					if(isset($config['active']) && $config['active'])
					{
						$plugins_translations[$plugin] 	= $storage->getYaml('pluginFolder', $plugin, $language . '.yaml');
					}
			  	}

  				foreach($plugins_translations as $key => $value)
  				{
					if(is_array($value))
					{
	  					$plugins_translations = array_merge($plugins_translations, $value);
					}
  				}
	  		}
		}

		$translations = [];
		if(is_array($plugins_translations) && !empty($plugins_translations))
		{
	  		$translations = array_merge($translations, $plugins_translations);
		}
		if(is_array($system_translations) && !empty($system_translations))
		{
	  		$translations = array_merge($translations, $system_translations);
		}
		if(is_array($theme_translations) && !empty($theme_translations))
		{
	  		$translations = array_merge($translations, $theme_translations);
		}
		return $translations;
	}

	public static function whichLanguage()
	{
		# Check which languages are available
		$langs = self::getLanguages();
	
		# Detect browser language
		$accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : false;
		$lang = in_array($accept_lang, $langs) ? $accept_lang : 'en';

		return $lang;
	}

	public static function getLanguages()
	{
		# Check which languages are available
		$langs = [];
		$path = __DIR__ . '/../author/translations/*.yaml';
		
		foreach (glob($path) as $filename) 
		{
			$langs[] = basename($filename,'.yaml');
		}
		
		return $langs;
	}

	# this just returns the string so you can use translate-function in system files. Everything that is wrapped in translate function will be added to translation files
	public static function translate(string $string)
	{
		return $string;
	}
}