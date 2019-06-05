<?php 

namespace Typemill;

class Settings
{	
	public static function loadSettings()
	{
		$defaultSettings 	= self::getDefaultSettings();
		$userSettings 		= self::getUserSettings();
		
		$settings 			= $defaultSettings;

		if($userSettings)
		{
			$settings 			= array_merge($defaultSettings, $userSettings);
		}
				
		return array('settings' => $settings);
	}
	
	public static function getDefaultSettings()
	{
		$rootPath = __DIR__ . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR;
		
		return [
			'determineRouteBeforeAppMiddleware' 	=> true,
			'displayErrorDetails' 					=> false,
			'title'									=> 'TYPEMILL',
			'author'								=> 'Unknown',
			'copyright'								=> 'Copyright',
			'language'								=> 'en',
			'startpage'								=> true,
			'rootPath'								=> $rootPath,
			'theme'									=> ($theme = 'typemill'),
			'themeFolder'							=> ($themeFolder = 'themes'),
			'themeBasePath'							=> $rootPath,
			'themePath'								=> $rootPath . $themeFolder . DIRECTORY_SEPARATOR . $theme,
			'settingsPath'							=> $rootPath . 'settings',
			'userPath'								=> $rootPath . 'settings' . DIRECTORY_SEPARATOR . 'users',
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'editor'								=> 'raw',
			'contentFolder'							=> 'content',
			'cache'									=> true,
			'cachePath'								=> $rootPath . 'cache',
			'version'								=> '1.2.15',
			'setup'									=> true,
			'welcome'								=> true,
			'images'								=> ['live' => ['width' => 820], 'mlibrary' => ['width' => 50, 'height' => 50]],
		];
	}
	
	public static function getUserSettings()
	{
		$yaml = new Models\WriteYaml();
		
		$userSettings = $yaml->getYaml('settings', 'settings.yaml');
		
		return $userSettings;
	}

	public static function getObjectSettings($objectType, $objectName)
	{
		$yaml = new Models\WriteYaml();
		
		$objectFolder 	= $objectType . DIRECTORY_SEPARATOR . $objectName;
		$objectFile		= $objectName . '.yaml';
		$objectSettings = $yaml->getYaml($objectFolder, $objectFile);

		return $objectSettings;
	}
	
	public static function createSettings()
	{
		$yaml = new Models\WriteYaml();
		
		# create initial settings file with only setup false
		if($yaml->updateYaml('settings', 'settings.yaml', array('setup' => false)))
		{
			return true; 
		}
		return false;
	}

	public static function updateSettings($settings)
	{
		# only allow if usersettings already exists (setup has been done)
		$userSettings 	= self::getUserSettings();
		
		if($userSettings)
		{
			# whitelist settings that can be stored in usersettings (values are not relevant here, only keys)			
			$allowedUserSettings = ['displayErrorDetails' => false,
									'title' => false,
									'copyright' => false,
									'language' => false,
									'startpage' => false,
									'author' => false,
									'year' => false,
									'theme' => false,
									'editor' => false,
									'setup' => false,
									'welcome' => false,
									'images' => false,
									'plugins' => false,
									'themes' => false,
									'latestVersion' => false 
								];

			# cleanup the existing usersettings
			$userSettings = array_intersect_key($userSettings, $allowedUserSettings);

			# cleanup the new settings passed as an argument
			$settings 	= array_intersect_key($settings, $allowedUserSettings);
			
			# merge usersettings with new settings
			$settings 	= array_merge($userSettings, $settings);

			/* write settings to yaml */
			$yaml = new Models\WriteYaml();
			$yaml->updateYaml('settings', 'settings.yaml', $settings);					
		}
	}
}