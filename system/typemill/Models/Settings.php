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

	public function getKixoteSettings()
	{
	    $defaultSettings = $this->storage->getYaml('systemSettings', '', 'kixote.yaml');
	    $userSettings = $this->storage->getYaml('settingsFolder', '', 'kixote.yaml');

	    if ($userSettings)
	    {
	        foreach ($defaultSettings['promptlist'] as $key => $prompt)
	        {
	            if (isset($userSettings['promptlist'][$key]))
	            {
	                # Use active setting from user but keep system settings intact
	            	$active = $userSettings['promptlist'][$key]['active']; 
	                $userSettings['promptlist'][$key] = $prompt;
	                $userSettings['promptlist'][$key]['active'] = $active;
	            }
	            else
	            {
	                # New prompt from system settings, add it to user settings
	                $userSettings['promptlist'][$key] = $prompt;
	            }
	        }
	    }
	    else
	    {
	        $userSettings = $defaultSettings;
	    }

	    return $userSettings;
	}

	public function updateKixoteSettings($kixoteSettings)
	{
		if($this->storage->updateYaml('settingsFolder', '', 'kixote.yaml', $kixoteSettings))
		{
			return true;
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
	
	public function updateSettings($newSettings, $key1 = false, $key2 = false)
	{
		$userSettings 	= $this->getUserSettings();

		# only allow if usersettings already exists (setup has been done)
		if($userSettings)
		{
			# hard overwrite
			if($key1 && $key2)
			{
				$userSettings[$key1][$key2] = $newSettings;
				$settings = $userSettings;
			}
			# hard overwrite
			elseif($key1)
			{
				$userSettings[$key1] = $newSettings;
				$settings = $userSettings;
			}
			# replace system settings
			else
			{
				# keep old plugin and theme settings unless explicitly replaced
				$plugins = $newSettings['plugins'] ?? ($userSettings['plugins'] ?? []);
				$themes  = $newSettings['themes'] ?? ($userSettings['themes'] ?? []);

				# remove them from $newSettings to avoid overwriting twice
				unset($newSettings['plugins'], $newSettings['themes']);

				# add current theme from usersettings
				$newSettings['theme']	 = $userSettings['theme'];

				# final combined settings
				$settings = array_merge($newSettings, [
				    'plugins' => $plugins,
				    'themes'  => $themes,
				]);
			}

			if($this->storage->updateYaml('settingsFolder', '', 'settings.yaml', $settings))
			{
				return true;
			}
		}

		return false;
	}

	private function array_is_list(array $arr)
	{
		if ($arr === [])
		{
			return true;
		}
		return array_keys($arr) === range(0, count($arr) - 1);
	}


	public function updateThemeCss(string $name, string $css)
	{
		if($css == '')
		{
			if($this->storage->deleteFile('cacheFolder', '', $name . '-custom.css', $css))
			{
				return true;
			}
		}
		else
		{
			if($this->storage->writeFile('cacheFolder', '', $name . '-custom.css', $css))
			{
				return true;
			}
		}

		return false;
	}

	public function getSettingsDefinitions()
	{	
		$settingsDefinitions = $this->storage->getYaml('systemSettings', '', 'system.yaml');

		if(!isset($settingsDefinitions['fieldsetsystem']['fields']['language']))
		{
			die('languages in settings-definitions are missing');
		}
	
		# get languages dynamically from existing translation-files	
		$languages = Translations::getLanguages();
		$langs = [];
		foreach($languages as $language)
		{
			$langs[$language] = $language;
		}

		$settingsDefinitions['fieldsetsystem']['fields']['language']['options'] = $langs;

		return $settingsDefinitions;
	}

	public function createSettings(array $defaultSettings = NULL)
	{
		$defaults = [
			'language' => Translations::whichLanguage()
		];

		if($defaultSettings)
		{
			$defaults = array_merge($defaults, $defaultSettings);
		}
    
    	$initialSettings = $this->storage->updateYaml('settingsFolder', '', 'settings.yaml', $defaults);

		if($initialSettings)
		{
			return true; 
		}
	
		return false;
	}

    public function findSecurityDefinitions($definitions, $securityDefinitions = [])
    {
        foreach ($definitions as $fieldname => $definition)
        {
            if (isset($definition['fields']))
            {
                $securityDefinitions = $this->findSecurityDefinitions($definition['fields'], $securityDefinitions);
            }

            if (isset($definition['type']) && $definition['type'] === 'password')
            {
                $securityDefinitions[] = $fieldname;
            }
        }

        return $securityDefinitions;
    }

    public function extractSecuritySettings($settings, $securityFields)
    {
        $securitySettings = [];

        foreach ($securityFields as $fieldname)
        {
            if (isset($settings[$fieldname]))
            {
                $securitySettings[$fieldname] = $settings[$fieldname];
                unset($settings[$fieldname]);
            }
        }

        return [
            'settings' => $settings,
            'securitySettings' => $securitySettings
        ];
    }

    public function updateSecuritySettings($newSecuritySettings, $themeorplugin = null, $themeorpluginname = null)
    {
        # problem that settings with same name will overwrite (e.g. from theme and plugins)
        $securitySettings = $this->getSecuritySettings();
        foreach($newSecuritySettings as $fieldname => $value)
        {
            if($themeorplugin && $themeorpluginname)
            {
                $securitySettings[$themeorplugin][$themeorpluginname][$fieldname] = $value;
            }
            else
            {
                $securitySettings[$fieldname] = $value;
            }
        }

        $secrets = $this->storage->updateYaml('settingsFolder', '', 'secrets.yaml', $securitySettings);
        if($secrets)
        {
            return true; 
        }

        return false;
    }

    private function getSecuritySettings()
    {
        $secrets = $this->storage->getYaml('settingsFolder', '', 'secrets.yaml');

        if($secrets)
        {
            return $secrets;
        }
        return [];
    }

    public function getSecret(string $fieldname, $objecttype = null, $objectname = null)
    {
        $secrets = $this->storage->getYaml('settingsFolder', '', 'secrets.yaml');

        if(!$secrets)
        {
            return false;
        }

        if($fieldname && $objecttype && $objectname)
        {
            if(isset($secrets[$objecttype][$objectname][$fieldname]))
            {
                return $secrets[$objecttype][$objectname][$fieldname];
            }
        }

        if($fieldname && isset($secrets[$fieldname]))
        {
            return $secrets[$fieldname];
        }

        return false;
    }
}