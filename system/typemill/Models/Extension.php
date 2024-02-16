<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class Extension
{
	private $storage;

	public function __construct()
	{
		$this->storage 	= new StorageWrapper('\Typemill\Models\Storage');
	}

	public function getThemeDetails($activeThemeName = NULL)
	{
		$themes = $this->getThemes();

		$themeDetails = [];
		foreach($themes as $themeName)
		{
			$details = $this->getThemeDefinition($themeName);
			if($details && isset($details['name']))
			{
				# add to first position if active
				if($activeThemeName && ($activeThemeName == $themeName))
				{
					$themeDetails = array_merge(array($themeName => $details), $themeDetails);
				}
				else
				{
					$themeDetails[$themeName] = $details;
				}
			}
		}

		return $themeDetails;
	}

	public function getThemeSettings($themesInSettings)
	{
		# WHAT ABOUT DEFAULT-SETTINGS FROM THEME YAMLs?

		$themes = $this->getThemes();

		$themeSettings = [];
		foreach($themes as $themename)
		{
			$themeinputs = [];
			if(isset($themesInSettings[$themename]) && is_array($themesInSettings[$themename]))
			{
				$themeinputs = $themesInSettings[$themename];
			}

			$themeSettings[$themename] = $themeinputs;
			$customcss = $this->storage->getFile('cacheFolder', '', $themename . '-custom.css');
			$themeSettings[$themename]['customcss'] = $customcss ? $customcss : '';
		}

		return $themeSettings;
	}


	public function getThemes()
	{
		$themeFolder 	= $this->storage->getFolderPath('themesFolder');
		$themeFolderC 	= scandir($themeFolder);
		$themes 		= [];
		foreach ($themeFolderC as $key => $theme)
		{
			if (!in_array($theme, [".",".."]))
			{
				if (is_dir($themeFolder . DIRECTORY_SEPARATOR . $theme))
				{
					$themes[] = $theme;
				}
			}
		}

		return $themes;
	}

	public function getThemeDefinition($themeName)
	{
		$themeSettings 		= $this->storage->getYaml('themesFolder', $themeName, $themeName . '.yaml');

		# add standard-textarea for custom css
		$themeSettings['forms']['fields']['customcss'] = [
			'type' 			=> 'codearea', 
			'label' 		=> Translations::translate('Custom CSS'), 
			'class' 		=> 'codearea', 
			'description' 	=> Translations::translate('You can overwrite the theme-css with your own css here.')
		];

		$themeSettings['preview'] = '/themes/' . $themeName . '/' . $themeName . '.png';

		return $themeSettings;
	}

	public function getPluginDetails($userSettings = NULL)
	{
		$plugins = $this->getPlugins();

		$pluginDetails = [];
		foreach($plugins as $pluginName)
		{
			$details = $this->getPluginDefinition($pluginName);
			if($details && $details['name'])
			{
				# add active plugins first
				if(
					$userSettings
					&& isset($userSettings[$pluginName]) 
					&& ($userSettings[$pluginName]['active'] == true) 
				)
				{
					$pluginDetails = array_merge(array($pluginName => $details), $pluginDetails);
				}
				else
				{
					$pluginDetails[$pluginName] = $details;
				}
			}
		}
		return $pluginDetails;
	}

	public function getPlugins()
	{

		$pluginlist = \Typemill\Static\Plugins::loadPlugins();

		$plugins = [];

		foreach($pluginlist as $plugin)
		{
			$plugins[] = $plugin['name'];
		}

		return $plugins;
	}

	public function getPluginDefinition($pluginName)
	{
		$pluginSettings 	= $this->storage->getYaml('pluginsFolder', $pluginName, $pluginName . '.yaml');

		return $pluginSettings;
	}
}