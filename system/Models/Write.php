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

	public function checkPath($folder)
	{
		$folderPath = $this->basePath . $folder;
		
		if(!is_dir($folderPath))
		{
			if(@mkdir($folderPath, 0774, true))
			{
				return true;
			}
			else
			{
				throw new \Exception("The folder '{$folder}' is missing and we could not create it. Please create the folder manually on your server.");
				return false;				
			}
		}
		
		if(@is_writable($folderPath))
		{
			return true;
		}
		else
		{
			throw new \Exception("Please make the folder '{$folder}' writable.");
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

	public function writeFile($folder, $file, $data)
	{
		if($this->checkPath($folder))
		{
			$filePath 	= $this->basePath . $folder . DIRECTORY_SEPARATOR . $file;
			$openFile 	= @fopen($filePath, "w");
			
			if(!$openFile)
			{
				return false;
			}
			
			fwrite($openFile, $data);
			fclose($openFile);
			
			return true;
		}
		return false;
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

	public function moveElement($item, $folderPath, $index)
	{
		$filetypes			= array('md', 'txt');
		
		# set new order as string
		$newOrder			= ($index < 10) ? '0' . $index : $index;

		# create new path with foldername or filename but without file-type
		$newPath 			= $this->basePath . 'content' . $folderPath . DIRECTORY_SEPARATOR . $newOrder . '-' . $item->name;
		
		if($item->elementType == 'folder')
		{
			$oldPath = $this->basePath . 'content' . $item->path;
			if(@rename($oldPath, $newPath))
			{
				return true;
			}
			return false;
		}
		
		# create old path but without filetype
		$oldPath		= substr($item->path, 0, strpos($item->path, "."));
		$oldPath		= $this->basePath . 'content' . $oldPath;
				
		$result 		= true;
		
		foreach($filetypes as $filetype)
		{
			$oldFilePath = $oldPath . '.' . $filetype;
			$newFilePath = $newPath . '.' . $filetype;
			
			#check if file with filetype exists and rename
			if($oldFilePath != $newFilePath && file_exists($oldFilePath))
			{
				if(@rename($oldFilePath, $newFilePath))
				{
					$result = $result;
				}
				else
				{
					$result = false;
				}
			}
		}

		return $result;
		
		/*
		if($item->elementType == 'folder')
		{
			$newName		= $newOrder . '-' . $item->name;
		}
		else
		{
			$newName		= $newOrder . '-' . $item->name . '.' . $item->fileType;
		}
		
		$oldPath			= $this->basePath . 'content' . $item->path;
		$newPath 			= $this->basePath . 'content' . $folderPath . DIRECTORY_SEPARATOR . $newName;

		if(@rename($oldPath, $newPath))
		{
			$result = true;
		}
		
		foreach($filetypes as $filetype)
		{
			#check if file exists
			if(file_exists($oldPath))
			{

			}
		}
		
		
		# if it is a txt file, check, if there is a corresponding .md file and move it
		if($result && $item->elementType == 'file' && $item->fileType == 'txt')
		{
			$result = false;
			
			$oldPath		= substr($item->path, 0, strpos($item->path, "."));
			$oldPath		= $this->basePath . 'content' . $oldPath . '.md';

			if(file_exists($oldPath))
			{
				$newName			= $newOrder . '-' . $item->name . '.md';
				$newPath 			= $this->basePath . 'content' . $folderPath . DIRECTORY_SEPARATOR . $newName;
				
				if(@rename($oldPath, $newPath))
				{
					$result = true;
				}
			}
		}
		
		return $result;
		*/
		
	}
}