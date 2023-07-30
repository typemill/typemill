<?php 

namespace Typemill\Static;

use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class Settings
{
	public static function loadSettings()
	{
		echo debug_backtrace()[1]['function'];
		die('use model load settings instead');
		$defaultsettings 	= self::getDefaultSettings();
		$usersettings 		= self::getUserSettings();
		
		$settings 			= $defaultsettings;
		$settings['setup'] 	= true;

		if($usersettings)
		{
			$settings 		= array_merge($defaultsettings, $usersettings);
			
			# make sure all image-size information are there
			if(isset($usersettings['images']))
			{
				$images = array_merge($defaultsettings['images'], $settings['images']);
				$settings['images'] = $images;
			}
		}

		$settings['rootPath'] = getcwd();
		$settings = self::addThemeSettings($settings);

		return $settings;
	}

	public static function addThemeSettings($settings)
	{
				echo debug_backtrace()[1]['function'];
		die('use model addThemeSettings instead');

		# we have to check if the theme has been deleted
		$rootpath		= getcwd();
		$themefolder 	= $rootpath . DIRECTORY_SEPARATOR . $settings['themeFolder'] . DIRECTORY_SEPARATOR;

		# if there is no theme in settings or theme has been deleted
		if(!isset($settings['theme']) OR !file_exists($themefolder . $settings['theme']))
		{
			# scan theme folder and get the first theme
			$themes = array_filter(scandir($themefolder), function ($item) use($themefolder)
			{
				return is_dir($themefolder . $item) && strpos($item, '.') !== 0;
			});

			$firsttheme = reset($themes);

			# if there is a theme with an index.twig-file
			if($firsttheme && file_exists($themefolder . $firsttheme . DIRECTORY_SEPARATOR . 'index.twig'))
			{
				$settings['theme'] = $firsttheme;
			}
			else
			{
				die('You need at least one theme with an index.twig-file in your theme-folder.');
			}
		}

		# We have the theme so create the theme path 
		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];

		# if there are no theme settings yet (e.g. no setup yet) use default theme settings
		if(!isset($settings['themes']))
		{
			$themeSettings = self::getObjectSettings('themes', $settings['theme']);
			$settings['themes'][$settings['theme']] = isset($themeSettings['settings']) ? $themeSettings['settings'] : false;
		}

		return $settings;
	}
	
	public static function getDefaultSettings()
	{
		echo debug_backtrace()[1]['function'];
		die('use model getDefaultSettings instead');

		$rootpath				= getcwd();
		$defaultsettingspath 	= $rootpath . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'typemill' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
		$defaultsettingsfile 	= $defaultsettingspath . 'defaults.yaml';

		if(file_exists($defaultsettingsfile))
		{
			$defaultsettingsyaml 					= file_get_contents($defaultsettingsfile);
			$defaultsettings 						= \Symfony\Component\Yaml\Yaml::parse($defaultsettingsyaml);
			$defaultsettings['defaultSettingsPath'] = $defaultsettingspath;
			
			return $defaultsettings;
		}

		return false;
	}
	
	public static function getUserSettings()
	{	
		echo debug_backtrace()[1]['function'];
		die('use model getUserSettings instead');

		$rootpath				= getcwd();
		$usersettingsfile 		= $rootpath . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'settings.yaml';

		if(file_exists($usersettingsfile))
		{
			$usersettingsyaml 	= file_get_contents($usersettingsfile);
			$usersettings 		= \Symfony\Component\Yaml\Yaml::parse($usersettingsyaml);
			
			return $usersettings;
		}

		return false;
	}

	public static function getObjectSettings($objectType, $objectName, $storagepath = '\Typemill\Models\Storage')
	{
		echo debug_backtrace()[1]['function'];
		die('use model getObjectSettings instead');

		$storage 	= new StorageWrapper($storagepath);

		$objectSettings = $storage->getYaml($objectType, $objectName, $objectName . '.yaml');

		if($objectSettings)
		{
			return $objectSettings;
		}
		return false;

/*
		$rootpath 		= getcwd();
		$objectfile 	= $rootpath . DIRECTORY_SEPARATOR . $objectType . DIRECTORY_SEPARATOR . $objectName . DIRECTORY_SEPARATOR . $objectName . '.yaml';

		if(file_exists($objectfile))
		{
			$objectsettingsyaml 	= file_get_contents($objectfile);
			$objectsettings 		= \Symfony\Component\Yaml\Yaml::parse($objectsettingsyaml);
			
			return $objectsettings;
		}

		return false;
		*/
	}

	public static function updateSettings(array $newSettings, $storagepath = '\Typemill\Models\Storage')
	{
		echo debug_backtrace()[1]['function'];
		die('use model updateSettings instead');

		$storage 	= new StorageWrapper($storagepath);

		# only allow if usersettings already exists (setup has been done)
		$userSettings 	= self::getUserSettings();

		# merge usersettings with new settings
		$settings 	= array_merge($userSettings, $newSettings);
				
		$storage->updateYaml('settingsFolder', '', 'settings.yaml', $settings);
	}

	public static function getSettingsDefinitions($storagepath = '\Typemill\Models\Storage')
	{
		echo debug_backtrace()[1]['function'];
		die('use model getSettingsDefinitions instead');

		$storage  = new StorageWrapper($storagepath);
		
		return $storage->getYaml('systemSettings', '', 'system.yaml');
	}

	public static function createSettings($storagepath = '\Typemill\Models\Storage')
	{
		echo debug_backtrace()[1]['function'];
		die('use model createSettings instead');

		$storage  = new StorageWrapper($storagepath);

    	$language = Translations::whichLanguage();
    
    	$initialSettings = $storage->updateYaml('settingsFolder', '', 'settings.yaml', [
			'language' => $language
		]);

		if($initialSettings)
		{
			return true; 
		}
		return false;
	}	
}