<?php 

namespace Typemill;

class Settings
{	
	public static function loadSettings()
	{
		$settings 			= self::getDefaultSettings();
		$userSettings 		= self::getUserSettings();
		
		if($userSettings)
		{
			$settings = array_merge($settings, $userSettings);
			$settings['setup'] = false;
		}
		
		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];
		
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
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'contentFolder'							=> 'content',
			'version'								=> '1.1.2',
			'setup'									=> true
		];
	}
	
	private static function getUserSettings()
	{
		$yaml = new Models\WriteYaml();
		
		$userSettings = $yaml->getYaml('settings', 'settings.yaml');
		
		return $userSettings;
	}
	
	public static function getPluginSettings($pluginName)
	{
		$yaml = new Models\WriteYaml();
		
		$pluginFolder 	= 'plugins' . DIRECTORY_SEPARATOR . $pluginName;
		$pluginFile		= $pluginName . '.yaml';
		$pluginSettings = $yaml->getYaml($pluginFolder, $pluginFile);

		return $pluginSettings;
	}

	public static function getThemeSettings($themeName)
	{
		$yaml = new Models\WriteYaml();
		
		$themeFolder 	= 'themes' . DIRECTORY_SEPARATOR . $themeName;
		$themeFile		= $themeName . '.yaml';
		$themeSettings = $yaml->getYaml($themeFolder, $themeFile);

		return $themeSettings;
	}
	
	public static function updateSettings($settings)
	{
		$yaml = new Models\WriteYaml();
		
		/* write settings to yaml */
		$yaml->updateYaml('settings', 'settings.yaml', $settings);
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
			
			$pluginSettings = self::getPluginSettings($pluginName);
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