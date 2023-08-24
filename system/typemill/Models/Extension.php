<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;

class Extension
{
	private $storage;

	public function __construct()
	{
		$this->storage 	= new StorageWrapper('\Typemill\Models\Storage');
	}

	public function getThemeDetails()
	{
		$themes = $this->getThemes();

		$themeDetails = [];
		foreach($themes as $themeName)
		{
			$themeDetails[$themeName] = $this->getThemeDefinition($themeName);
		}

		return $themeDetails;
	}

	public function getThemeSettings($themes)
	{
		$themeSettings = [];
		foreach($themes as $themename => $themeinputs)
		{
			if(!is_array($themeinputs)){ $themeinputs = []; }
			$themeSettings[$themename] = $themeinputs;
			$themeSettings[$themename]['customcss'] = $this->storage->getFile('cacheFolder', '', $themename . '-custom.css');
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
			'label' 		=> 'Custom CSS', 
			'class' 		=> 'codearea', 
			'description' 	=> 'You can overwrite the theme-css with your own css here.'
		];

# add image preview file 
		$themeSettings['preview'] = '/themes/' . $themeName . '/' . $themeName . '.png';

		return $themeSettings;
	}

	public function getPluginDetails()
	{
		$plugins = $this->getPlugins();

		$pluginDetails = [];
		foreach($plugins as $pluginName)
		{
			$pluginDetails[$pluginName] = $this->getPluginDefinition($pluginName);
		}

		return $pluginDetails;
	}

	public function getPluginSettings($plugins)
	{
		$pluginSettings 	= [];
		foreach($plugins as $pluginname => $plugininputs)
		{
			$pluginSettings[$pluginname] = $plugininputs;
		}

		return $pluginSettings;
	}

	public function getPlugins()
	{
		$pluginFolder 	= $this->storage->getFolderPath('pluginsFolder');
		$pluginFolderC 	= scandir($pluginFolder);
		$plugins 		= [];
		foreach ($pluginFolderC as $key => $plugin)
		{
			if (!in_array($plugin, [".",".."]))
			{
				if (is_dir($pluginFolder . DIRECTORY_SEPARATOR . $plugin))
				{
					$plugins[] = $plugin;
				}
			}
		}

		return $plugins;
	}

	public function getPluginDefinition($pluginName)
	{
		$pluginSettings 	= $this->storage->getYaml('pluginsFolder', $pluginName, $pluginName . '.yaml');

		return $pluginSettings;
	}
}