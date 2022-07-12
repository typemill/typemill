<?php 

namespace Typemill\Static;

class Settings
{
	public static function loadSettings($rootpath)
	{
		$defaultsettings 	= self::getDefaultSettings($rootpath);
		$usersettings 		= self::getUserSettings($rootpath);
		
		$settings 			= $defaultsettings;

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

		$settings = self::addThemeSettings($settings);

		return $settings;
	}

	public static function addThemeSettings($settings)
	{
		# we have to check if the theme has been deleted
		$themefolder = $settings['rootPath'] . DIRECTORY_SEPARATOR . $settings['themeFolder'] . DIRECTORY_SEPARATOR;

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
	
	public static function getDefaultSettings($rootpath)
	{
		$defaultsettingspath 	= $rootpath . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'typemill' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
		$defaultsettingsfile 	= $defaultsettingspath . 'defaults.yaml';

		if(file_exists($defaultsettingsfile))
		{
			$defaultsettingsyaml 					= file_get_contents($defaultsettingsfile);
			$defaultsettings 						= \Symfony\Component\Yaml\Yaml::parse($defaultsettingsyaml);
			$defaultsettings['rootPath'] 			= $rootpath;
			$defaultsettings['defaultSettingsPath'] = $defaultsettingspath;
			
			return $defaultsettings;
		}

		return false;
	}
	
	public static function getUserSettings($rootpath)
	{	
		$usersettingsfile 	= $rootpath . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'settings.yaml';

		if(file_exists($usersettingsfile))
		{
			$usersettingsyaml 	= file_get_contents($usersettingsfile);
			$usersettings 		= \Symfony\Component\Yaml\Yaml::parse($usersettingsyaml);
			
			return $usersettings;
		}

		return false;
	}




### refactor


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

    	$language = self::whichLanguage();
    
		# create initial settings file with only setup false
		if($yaml->updateYaml('settings', 'settings.yaml', array('setup' => false, 'language' => $language)))
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
			$allowedUserSettings = ['displayErrorDetails' => true,
									'title' => true,
									'copyright' => true,
									'language' => true,
									'langattr' => true,
									'startpage' => true,
									'author' => true,
									'year' => true,
									'access' => true,
									'pageaccess' => true,
									'hrdelimiter' => true,
									'restrictionnotice' => true,
									'wraprestrictionnotice' => true,
									'headlineanchors' => true,
									'theme' => true,
									'editor' => true,
									'formats' => true,
									'setup' => true,
									'welcome' => true,
									'images' => true,
									'live' => true,
									'width' => true,
									'height' => true,
									'plugins' => true,
									'themes' => true,
									'latestVersion' => true,
									'logo' => true,
									'favicon' => true,
									'twigcache' => true,
									'proxy' => true,
									'trustedproxies' => true,
									'headersoff' => true,
									'urlschemes' => true,
									'svg' => true,
									'recoverpw' => true,
									'recoversubject' => true,
									'recovermessage' => true,
									'recoverfrom' => true,
									'securitylog' => true,
									'oldslug' => true,
									'refreshcache' => true,
									'pingsitemap' => true,
								];

			# cleanup the existing usersettings
			$userSettings = array_intersect_key($userSettings, $allowedUserSettings);

			# cleanup the new settings passed as an argument
			$settings 	= array_intersect_key($settings, $allowedUserSettings);
			
			# merge usersettings with new settings
			$settings 	= array_merge($userSettings, $settings);

			# write settings to yaml
			$yaml = new Models\WriteYaml();
			$yaml->updateYaml('settings', 'settings.yaml', $settings);					
		}
	}
	
}