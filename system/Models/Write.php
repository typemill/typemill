<?php

namespace Typemill\Models;

class Write
{
	protected $basePath;
		
	public function __construct()
	{
		$basePath			= getcwd() . DIRECTORY_SEPARATOR;
		$this->basePath 	= $basePath;
	}
		
	protected function checkPath($folder)
	{
		$folderPath = $this->basePath . $folder;
		
		if(!is_dir($folderPath) AND !mkdir($folderPath, 0774, true))
		{
			throw new Exception("The folder '{$folder}' is missing and we could not create it. Please create the folder manually on your server.");
			return false;
		}
		
		if(!is_writable($folderPath))
		{
			throw new Exception("Please make the folder '{$folder}' writable.");
			return false;
		}
		return true;
	}
	
	protected function checkFile($folder, $file)
	{		
		if(!file_exists($this->basePath . $folder . DIRECTORY_SEPARATOR . $file))
		{
			return false;
		}
		return true;
	}

	protected function writeFile($folder, $file, $data)
	{
		$filePath 	= $this->basePath . $folder . DIRECTORY_SEPARATOR . $file;
		$openFile 	= fopen($filePath, "w");
		
		fwrite($openFile, $data);
		fclose($openFile);
	}
	
	public function getFile($folderName, $fileName)
	{
		if($this->checkFile($folderName, $fileName))
		{
			$fileContent = file_get_contents($folderName . DIRECTORY_SEPARATOR . $fileName);
			return $fileContent;
		}
		return false;
	}
}