<?php 

namespace Typemill;

class Settings
{	
	public static function loadSettings()
	{
		$settings 			= self::getDefaultSettings();
		$userSettings 		= self::getUserSettings($settings['settingsPath']);
		
		if($userSettings)
		{
			$settings = array_merge($settings, $userSettings);
		}
		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];
		return array('settings' => $settings);
	}
	
	private function getDefaultSettings()
	{
		$rootPath = __DIR__ . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR;
		
		return [
			'determineRouteBeforeAppMiddleware' 	=> true,
			'displayErrorDetails' 					=> true,
			'title'									=> 'TYPEMILL',
			'author'								=> 'Unknown',
			'copyright'								=> 'Copyright',
			'startpage'								=> true,
			'rootPath'								=> $rootPath,
			'theme'									=> ($theme = 'typemill'),
			'themeFolder'							=> ($themeFolder = 'themes'),
			'themeBasePath'							=> $rootPath,
			'themePath'								=> $rootPath . $themeFolder . DIRECTORY_SEPARATOR . $theme,
			'settingsPath'							=> $rootPath . 'settings',
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'contentFolder'							=> 'content',
			'version'								=> '1.0.4'
		];
	}
	
	private function getUserSettings($settingsPath)
	{
		if(file_exists($settingsPath . DIRECTORY_SEPARATOR . 'settings.yaml'))
		{
			$yaml = new \Symfony\Component\Yaml\Parser();

			try {
				$userSettings 	= $yaml->parse( file_get_contents($settingsPath . DIRECTORY_SEPARATOR . 'settings.yaml' ) );
			} catch (ParseException $e) {
				printf("Unable to parse the YAML string: %s", $e->getMessage());
			}
			return $userSettings;
		}
		return false;
	}
}