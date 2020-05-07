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

		# no individual image sizes are allowed sind 1.3.4
		$settings['images']	= $defaultSettings['images'];

		# if there is no theme set
		if(!isset($settings['theme']))
		{
			# scan theme folder and get the first theme
			$themefolder = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR;
			$themes = array_diff(scandir($themefolder), array('..', '.'));
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

		# let us load translations only for admin area to improve performance for frontend
		$uri = $_SERVER['REQUEST_URI'];
		if(isset($uri) && (strpos($uri,'/tm/') !== false OR strpos($uri,'/setup') !== false))
		{
		    # i18n
		    # load the strings of the set language
		    $language = $settings['language'];
		    $theme = $settings['theme'];
		    $plugins = [];
		    if(isset($settings['plugins']))
		    {
	        	$plugins = $settings['plugins'];
	      	}
	      	$settings['labels'] = self::getLanguageLabels($language, $theme, $plugins);
		}

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
			'langattr'								=> 'en',
			'startpage'								=> true,
			'rootPath'								=> $rootPath,
			'themeFolder'							=> 'themes',
			'themeBasePath'							=> $rootPath,
			'themePath'								=> '',
			'settingsPath'							=> $rootPath . 'settings',
			'userPath'								=> $rootPath . 'settings' . DIRECTORY_SEPARATOR . 'users',
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'editor'								=> 'visual',
			'formats'								=> ['markdown', 'headline', 'ulist', 'olist', 'table', 'quote', 'image', 'video', 'file', 'toc', 'hr', 'definition', 'code'],
			'contentFolder'							=> 'content',
			'cache'									=> true,
			'cachePath'								=> $rootPath . 'cache',
			'version'								=> '1.3.6.1',
			'setup'									=> true,
			'welcome'								=> true,
			'images'								=> ['live' => ['width' => 820], 'thumbs' => ['width' => 250, 'height' => 150]],
		];
	}
	
	public static function getUserSettings()
	{
		$yaml = new Models\WriteYaml();
		
		$userSettings = $yaml->getYaml('settings', 'settings.yaml');
		
		return $userSettings;
	}


    # i18n
 	public static function getLanguageLabels($language, $theme, $plugins)
	{
    	# if not present, set the English language
    	if( empty($language) )
    	{
      		$language = 'en';
    	}

    	# loads the system strings of the set language
		$yaml = new Models\WriteYaml();
    	$system_labels = $yaml->getYaml('system' . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR . 'languages', $language . '.yaml');

    	# loads the theme strings of the set language
    	$theme_labels = [];
    	$theme_language_folder = 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
    	$theme_language_file = $language . '.yaml';
    	if (file_exists($theme_language_folder . $theme_language_file))
    	{
      		$theme_labels = $yaml->getYaml($theme_language_folder, $theme_language_file);
    	}

    	# loads the plugins strings of the set language
    	$plugins_labels = [];
    	if(!empty($plugins))
    	{
      		$plugin_labels = [];
      		foreach($plugins as $name => $value)
      		{
        		$plugin_language_folder = 'plugins' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
        		$plugin_language_file = $language . '.yaml';

        		if (file_exists($plugin_language_folder . $plugin_language_file))
        		{
          			$plugin_labels[$name] = $yaml->getYaml($plugin_language_folder, $plugin_language_file);
        		}
      		}
      		foreach($plugin_labels as $key => $value)
      		{
        		$plugins_labels = array_merge($plugins_labels, $value);
      		}
    	}

    	# Combines arrays of system languages, themes and plugins
    	$labels = array_merge($system_labels, $theme_labels, $plugins_labels);

		return $labels;
	}

  	public function whichLanguage()
  	{
    	# Check which languages are available
    	$langs = [];
    	$path = __DIR__ . '/author/languages/*.yaml';
    	
    	foreach (glob($path) as $filename) 
    	{
      		$langs[] = basename($filename,'.yaml');
    	}
    
    	# Detect browser language
    	$accept_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    	$lang = in_array($accept_lang, $langs) ? $accept_lang : 'en';

    	return $lang;
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
									'headlineanchors' => true,
									'theme' => true,
									'editor' => true,
									'formats' => true,
									'setup' => true,
									'welcome' => true,
									'images' => true,
									'plugins' => true,
									'themes' => true,
									'latestVersion' => true,
									'logo' => true,
									'favicon' => true, 
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
