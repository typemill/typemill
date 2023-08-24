<?php 

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class Settings
{
	private $storage;

	public function __construct()
	{
		$this->storage = new StorageWrapper('\Typemill\Models\Storage');
	}

	public function loadSettings()
	{
		$defaultsettings 	= $this->getDefaultSettings();
		$usersettings 		= $this->getUserSettings();
		
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
####
		$settings['rootPath'] = getcwd();
####
		$settings = self::addThemeSettings($settings);

		return $settings;
	}

	public function addThemeSettings($settings)
	{
		# we have to check if the theme has been deleted
		$themefolder = $this->storage->getFolderPath('themesFolder');

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
#		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];

		# if there are no theme settings yet (e.g. no setup yet) use default theme settings
		if(!isset($settings['themes']))
		{
			$themeSettings = $this->getObjectSettings('themes', $settings['theme']);
			$settings['themes'][$settings['theme']] = isset($themeSettings['settings']) ? $themeSettings['settings'] : false;
		}

		return $settings;
	}
	
	public function getDefaultSettings()
	{
		$defaultSettings = $this->storage->getYaml('systemSettings', '', 'defaults.yaml');

		if($defaultSettings)
		{
			$defaultSettings['systemSettingsPath'] = $this->storage->getFolderPath('systemSettings');
			
			return $defaultSettings;
		}

		return false;
	}
	
	public function getUserSettings()
	{	
		$userSettings = $this->storage->getYaml('settingsFolder', '', 'settings.yaml');

		if($userSettings)
		{
			return $userSettings;
		}

		return false;
	}
	
	public function getObjectSettings($objectType, $objectName)
	{
		$objectSettings = $this->storage->getYaml($objectType, $objectName, $objectName . '.yaml');

		if($objectSettings)
		{
			return $objectSettings;
		}

		return false;
	}

	public function updateSettings(array $newSettings)
	{		
		$userSettings 	= $this->getUserSettings();

		# only allow if usersettings already exists (setup has been done)
		if($userSettings)
		{
			# merge usersettings with new settings
			$settings 	= array_merge($userSettings, $newSettings);
			
			# make sure that multidimensional arrays are merged correctly
			# for example: only one plugin data will be passed with new settings, with array merge all others will be deleted.
			foreach($newSettings as $key => $settingsItem)
			{
				if(is_array($settingsItem) && isset($userSettings[$key]))
				{
					$settings[$key] = array_merge($userSettings[$key], $newSettings[$key]);
				}
			}

			if($this->storage->updateYaml('settingsFolder', '', 'settings.yaml', $settings))
			{
				return true;
			}
		}

		return false;
	}

	public function getSettingsDefinitions()
	{		
		return $this->storage->getYaml('systemSettings', '', 'system.yaml');
	}

	public function createSettings()
	{
    	$language = Translations::whichLanguage();
    
    	$initialSettings = $this->storage->updateYaml('settingsFolder', '', 'settings.yaml', [
			'language' => $language
		]);

		if($initialSettings)
		{
			return true; 
		}
		return false;
	}
}