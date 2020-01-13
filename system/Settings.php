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

    // i18n
    // load the strings of the set language
    $language = $settings['language'];
    $settings['labels'] = self::getLanguageLabels($language);
    $settings['vuejsLabels'] = self::getVuejsLabels($language);

		# We know the used theme now so create the theme path 
		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];

		# if there are no theme settings yet (e.g. no setup yet) use default theme settings
		if(!isset($settings['themes']))
		{
			$themeSettings = self::getObjectSettings('themes', $settings['theme']);
			$settings['themes'][$settings['theme']] = isset($themeSettings['settings']) ? $themeSettings['settings'] : false;
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
			'theme'									=> 'typemill',
			'themeFolder'							=> 'themes',
			'themeBasePath'							=> $rootPath,
			'themePath'								=> '',
			'settingsPath'							=> $rootPath . 'settings',
			'userPath'								=> $rootPath . 'settings' . DIRECTORY_SEPARATOR . 'users',
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'editor'								=> 'visual',
			'formats'								=> ['markdown', 'headline', 'ulist', 'olist', 'table', 'quote', 'image', 'video', 'toc', 'hr', 'definition', 'code'],
			'contentFolder'							=> 'content',
			'cache'									=> true,
			'cachePath'								=> $rootPath . 'cache',
			'version'								=> '1.3.1',
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


    // i18n
  public static function getLanguageLabels($language)
	{
    // if not present, set the English language
    if( empty($language) ){
      $language = 'en';
    }

    // load the strings of the set language
		$yaml = new Models\WriteYaml();
		$labels = $yaml->getYaml('settings/languages', $language.'.yaml');
		
		return $labels;
	}

  //public static function getVuejsLabels()
  public static function getVuejsLabels($language)
	{
    if( empty($language) ){//temp
      $language = 'en';//temp
    }//temp
    
    // load the strings of the set language
		$yaml = new Models\WriteYaml();
		//$labels = $yaml->getYaml('settings/languages', 'vue-blox.yaml');
    $labels = $yaml->getYaml('settings/languages', 'vuejs-'.$language.'.yaml');//temp
		
		return $labels;
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
									'formats' => false,
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