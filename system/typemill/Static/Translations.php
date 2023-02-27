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
		$theme_language_folder 	= 'themes' . DIRECTORY_SEPARATOR . $settings['theme'] . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $environment . DIRECTORY_SEPARATOR;
		$theme_language_file 	= $language . '.yaml';
		if (file_exists($theme_language_folder . $theme_language_file))
		{
			$theme_translations = $storage->getYaml($theme_language_folder, $theme_language_file);
		}

		if($environment == 'admin')
		{
			$system_language_folder = 'system' . DIRECTORY_SEPARATOR . 'typemill' . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;
			$system_language_file 	= $language . '.yaml';
			if(file_exists($system_language_folder . $system_language_file))
			{
				$system_translations = $storage->getYaml($system_language_folder, $system_language_file);
			}

			# Next change, to provide labels for the admin and user environments.
			# There may be plugins that only work in the user environment, only in the admin environment, or in both environments.
	  		$plugin_labels = [];
	  		if(isset($settings['plugins']) && !empty($settings['plugins']))
	  		{
			  	foreach($settings['plugins'] as $plugin => $config)
			  	{
					if(isset($config['active']) && $config['active'])
					{
				  		$plugin_language_folder = 'plugins' . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
				  		$plugin_language_file = $language . '.yaml';
				  		if (file_exists($plugin_language_folder . $plugin_language_file))
				  		{
							$plugins_translations[$plugin] = $storage->getYaml($plugin_language_folder, $plugin_language_file);
				  		}
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
		if(!empty($plugins_translations))
		{
	  		$translations = array_merge($translations, $plugins_translations);
		}
		if(!empty($system_translations))
		{
	  		$translations = array_merge($translations, $system_translations);
		}
		if(!empty($theme_translations))
		{
	  		$translations = array_merge($translations, $theme_translations);
		}

		return $translations;
	}

	public static function whichLanguage()
	{
		# Check which languages are available
		$langs = [];
		$path = __DIR__ . '/author/languages/*.yaml';
		
		foreach (glob($path) as $filename) 
		{
			$langs[] = basename($filename,'.yaml');
		}
	
		# Detect browser language
		$accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : false;
		$lang = in_array($accept_lang, $langs) ? $accept_lang : 'en';

		return $lang;
	}
}