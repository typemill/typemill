<?php

namespace Typemill\Models;

class Yaml extends StorageWrapper
{
	/**
	 * Get the a yaml file.
	 * @param string $fileName is the name of the Yaml Folder.
	 * @param string $yamlFileName is the name of the Yaml File.
	 */
	public function getYaml($folderName, $yamlFileName)
	{
		die('Yaml class outdated. Use storage instead.');
		$yaml = $this->getFile($folderName, $yamlFileName);
		
		if($yaml)
		{
			return \Symfony\Component\Yaml\Yaml::parse($yaml);
		}

		return false;
	}

	/**
	 * Writes a yaml file.
	 * @param string $fileName is the name of the Yaml Folder.
	 * @param string $yamlFileName is the name of the Yaml File.
	 * @param array $contentArray is the content as an array.
	 */	
	public function updateYaml($folderName, $yamlFileName, $contentArray)
	{
		die('Yaml class outdated. Use storage instead.');
		$yaml = \Symfony\Component\Yaml\Yaml::dump($contentArray,6);
		if($this->writeFile($folderName, $yamlFileName, $yaml))
		{
			return true;
		}

		return false;
	}
}