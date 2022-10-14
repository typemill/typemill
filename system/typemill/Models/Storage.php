<?php

namespace Typemill\Models;

class Storage
{
	protected $basepath;

	public $error = false;

	public function __construct()
	{
		$this->basepath 	= getcwd() . DIRECTORY_SEPARATOR;
	}

	public function checkFolder($folder)
	{
		$folderpath = $this->basepath . $folder;

		if(!is_dir($folderpath) OR !is_writable($folderpath))
		{
			$this->error = "The folder $folder does not exist or is not writable.";

			return false;
		}

		return true;
	}

	public function createFolder($folder)
	{
		$folderpath = $this->basepath . $folder;

		if(is_dir($folderpath))
		{
			return true;
		}

		if(!mkdir($folderpath, 0755, true))
		{
			$this->error = "Could not create folder $folder.";

			return false;
		}

		return true;
	}

	public function checkFile($folder, $filename)
	{
		if(!file_exists($this->basepath . $folder . DIRECTORY_SEPARATOR . $filename))
		{
			$this->error = "The file $filename in folder $folder does not exist.";

			return false;
		}

		return true;
	}

	public function writeFile($folder, $filename, $data)
	{
		if(!$this->checkFolder($folder))
		{
			if(!$this->createFolder($folder))
			{
				return false;
			}
		}

		$filepath = $this->basepath . $folder . DIRECTORY_SEPARATOR . $filename;
			
		$openfile = @fopen($filepath, "w");
		if(!$openfile)
		{
			$this->error = "Could not open and read the file $filename in folder $folder.";

			return true;
		}

		fwrite($openfile, $data);
		fclose($openfile);

		return true;
	}

	public function getFile($folder, $filename)
	{
		if($this->checkFile($folder, $filename))
		{
			# ??? should be with basepath???
			$fileContent = file_get_contents($folder . DIRECTORY_SEPARATOR . $filename);
			return $fileContent;
		}

		return false;
	}

	public function renameFile($folder, $oldname, $newname)
	{

		$oldFilePath = $this->basepath . $folder . DIRECTORY_SEPARATOR . $oldname;
		$newFilePath = $this->basepath . $folder . DIRECTORY_SEPARATOR . $newname;

		if(!file_exists($oldFilePath))
		{
			return false;
		}

		if(!rename($oldFilePath, $newFilePath))
		{
			return false;
		}
		
		return true;
	}

	public function deleteFile($filepath)
	{
		if($this->checkFileWithPath($filepath))
		{
			unlink($this->basePath . $filepath);
			return true;
		}
		return false;
	}

	public function getError()
	{
		return $this->error;
	}



/*
	public function checkPath($folder)
	{
		$folderPath = $this->basepath . $folder;

		if(!is_dir($folderPath))
		{
			if(@mkdir($folderPath, 0774, true))
			{
				return true;
			}
			else
			{
				throw new \Exception("The folder '{$folderPath}' is missing and we could not create it. Please create the folder manually on your server.");
#				return false;				
			}
		}
		
		if(@is_writable($folderPath))
		{
			return true;
		}
		else
		{
			throw new \Exception("Please make the folder '{$folderPath}' writable.");
#			return false;
		}
		return true;
	}

/*
	
	public function checkFile($folder, $file)
	{
		if(!file_exists($this->basePath . $folder . DIRECTORY_SEPARATOR . $file))
		{
			return false;
		}
		return true;
	}

	public function checkFileWithPath($filepath)
	{
		if(!file_exists($this->basePath . $filepath))
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

	public function getFileWithPath($filepath)
	{
		if($this->checkFileWithPath($filepath))
		{
			$fileContent = file_get_contents($filepath);
			return $fileContent;
		}
		return false;
	}

	public function deleteFileWithPath($filepath)
	{
		if($this->checkFileWithPath($filepath))
		{
			unlink($this->basePath . $filepath);
			return true;
		}
		return false;
	}

	public function renameFile($folder, $oldname, $newname)
	{

		$oldFilePath = $this->basePath . $folder . DIRECTORY_SEPARATOR . $oldname;
		$newFilePath = $this->basePath . $folder . DIRECTORY_SEPARATOR . $newname;

		if(!file_exists($oldFilePath))
		{
			return false;
		}

		if(@rename($oldFilePath, $newFilePath))
		{
			return true;
		}
		
		return false;
	}
	
	public function renamePost($oldPathWithoutType, $newPathWithoutType)
	{
		$filetypes			= array('md', 'txt', 'yaml');
				
		$oldPath 			= $this->basePath . 'content' . $oldPathWithoutType;
		$newPath 			= $this->basePath . 'content' . $newPathWithoutType;
						
		$result 			= true;
		
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
	}	

	public function moveElement($item, $folderPath, $index, $date = null)
	{
		$filetypes			= array('md', 'txt', 'yaml');
		
		# set new order as string
		$newOrder			= ($index < 10) ? '0' . $index : $index;

		# create new path with foldername or filename but without file-type
		# $newPath 			= $this->basePath . 'content' . $folderPath . DIRECTORY_SEPARATOR . $newOrder . '-' . str_replace(" ", "-", $item->name);
		
		$newPath 			= $this->basePath . 'content' . $folderPath . DIRECTORY_SEPARATOR . $newOrder . '-' . $item->slug;

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
	}
	*/
}