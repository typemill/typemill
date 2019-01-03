<?php 

namespace Typemill;

class Settings
{	
	public static function loadSettings()
	{
		$settings 			= self::getDefaultSettings();
		$userSettings 		= self::getUserSettings();

		$imgSettings 		= isset($settings['images']) ? $settings['images'] : [];
		
		if($userSettings)
		{
			$settings 			= array_merge($settings, $userSettings);
			$settings['setup'] 	= false;
		}
		
		$settings['images']		= isset($userSettings['images']) ? array_merge($imgSettings, $userSettings['images']) : $imgSettings;
		$settings['themePath'] 	= $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];

		return array('settings' => $settings);
	}
	
	private static function getDefaultSettings()
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
			'version'								=> '1.2.9',
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
	
	public static function createSettings($settings)
	{
		$yaml = new Models\WriteYaml();
		
		/* write settings to yaml */
		$yaml->updateYaml('settings', 'settings.yaml', $settings);
	}
	
	public static function updateSettings($settings)
	{
		$userSettings 	= self::getUserSettings();
		
		if($userSettings)
		{
			$yaml 		= new Models\WriteYaml();
			$settings 	= array_merge($userSettings, $settings);
			
			/* write settings to yaml */
			$yaml->updateYaml('settings', 'settings.yaml', $settings);					
		}
	}
	
	public static function removePluginSettings($pluginName)
	{
		$userSettings 	= self::getUserSettings();
		
		if($userSettings && isset($userSettings['plugins'][$pluginName]))
		{
			$yaml = new Models\WriteYaml();
			
			/* delete the plugin from settings */
			unset($userSettings['plugins'][$pluginName]);
			
			/* write settings to yaml */
			$yaml->updateYaml('settings', 'settings.yaml', $userSettings);			
		}
		
		return $userSettings;
	}
	
	public static function addPluginSettings($pluginName)
	{
		$userSettings 	= self::getUserSettings();
		
		if($userSettings)
		{
			$yaml = new Models\WriteYaml();
			
			$pluginSettings = self::getObjectSettings('plugins', $pluginName);
			if(isset($pluginSettings['settings']))
			{
				$userSettings['plugins'][$pluginName] = $pluginSettings['settings'];
			}
			
			$userSettings['plugins'][$pluginName]['active'] = false;
			
			/* write settings to yaml */
			$yaml->updateYaml('settings', 'settings.yaml', $userSettings);

			return $userSettings;
		}
		return false;
	}
}