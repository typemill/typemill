<?php

namespace Typemill\Models;

class Storage
{
	public $error 				= false;

	protected $basepath 		= false;

	protected $tmpFolder 		= false;

	protected $originalFolder 	= false;

	protected $liveFolder 		= false;

	protected $thumbsFolder 	= false;

	protected $customFolder 	= false;

	protected $fileFolder 		= false;

	protected $contentFolder 	= false;

	public function __construct()
	{
		$this->basepath 		= getcwd() . DIRECTORY_SEPARATOR;

		$this->tmpFolder 		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

		$this->originalFolder 	= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR;

		$this->liveFolder  		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR;

		$this->thumbsFolder		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR;

		$this->customFolder		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR;

		$this->fileFolder 		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
	
		$this->contentFolder 	= $this->basepath . 'content';
	}

	public function getError()
	{
		return $this->error;
	}

	public function getStorageInfo($item)
	{
		if(isset($this->$item))
		{
			return $this->$item;
		}
		return false;
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

	public function writeFile($folder, $filename, $data, $method = NULL)
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

			return false;
		}

		# serialize, json_decode
		if($method && is_callable($method))
		{
			$data = $method($data);
		}

		$writefile = fwrite($openfile, $data);
		if(!$writefile)
		{
			$this->error = "Could not write to the file $filename in folder $folder.";

			return false;
		}

		fclose($openfile);

		return true;
	}

	public function getFile($folder, $filename, $method = NULL)
	{
		if($this->checkFile($folder, $filename))
		{
			# ??? should be with basepath???
			$fileContent = file_get_contents($folder . DIRECTORY_SEPARATOR . $filename);
		
			# use unserialise or json_decode
			if($method && is_callable($method))
			{
				$fileContent = $method($fileContent);
			}

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

	public function deleteFile($folder, $filename)
	{
		if($this->checkFile($folder, $filename))
		{
			if(unlink($this->basepath . $folder . DIRECTORY_SEPARATOR . $filename))
			{
				return true;
			}

			$this->error = "We found the file but could not delete $filename";
		}

		return false;
	}

	public function moveFile()
	{

	}

	/**
	 * Get the a yaml file.
	 * @param string $fileName is the name of the Yaml Folder.
	 * @param string $yamlFileName is the name of the Yaml File.
	 */
	public function getYaml($folderName, $yamlFileName)
	{
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
		$yaml = \Symfony\Component\Yaml\Yaml::dump($contentArray,6);
		if($this->writeFile($folderName, $yamlFileName, $yaml))
		{
			return true;
		}

		return false;
	}




	public function createUniqueImageName($filename, $extension)
	{
		$defaultfilename = $filename;
	
		$suffix = 1;

		while(file_exists($this->originalFolder . $filename . '.' . $extension))
		{
			$filename = $defaultfilename . '-' . $suffix;
			$suffix++;
		}

		return $filename;
	}

	public function publishImage($name)
	{
		$pathinfo = pathinfo($name);
		if(!$pathinfo)
		{
			$this->errors[] = 'Could not read pathinfo.';

			return false;
		}

		$extension 	= isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : false;
		$imagename 	= isset($pathinfo['filename']) ? $pathinfo['filename'] : false;

		$imagesInTmp = glob($this->tmpFolder . "*$imagename.*"); 
		if(empty($imagesInTmp) OR !$imagesInTmp)
		{
			$this->errors[] = "We did not find the image in the tmp-folder or could not read it.";
			return false;
		}

		# case: image is not published yet and in tmp
		foreach( $imagesInTmp as $imagepath)
		{
			$tmpimagename 		= explode("+", basename($imagepath));
			$destinationfolder	= strtolower($tmpimagename[0]);
			$filename 			= $tmpimagename[1];

			switch($destinationfolder)
			{
				case 'original':
					if(!rename($imagepath, $this->originalFolder . $filename))
					{
						$this->errors[] = "We could not store the original image to the original folder";
					}
					break;
				case 'live':
					if(!rename($imagepath, $this->liveFolder . $filename))
					{
						$this->errors[] = "We could not store the live image to the live folder";
					}
					break;
				case 'thumbs':
					if(!rename($imagepath, $this->thumbsFolder . $filename))
					{
						$this->errors[] = "We could not store the thumb to the thumb folder";
					}
					break;
			}
		}

		if(empty($this->errors))
		{
			# return true;
			return 'media/live/' . $imagename . '.' . $extension;
		}

		return false;
	}

	# check if an image exists in the live folder or in the original folder independent from extension
	public function checkImage($imagepath)
	{
		$original 	= stripos($imagepath, '/original/');
		$live 		= stripos($imagepath, '/live/');

		$pathinfo = pathinfo($imagepath);
		if(!$pathinfo)
		{
			$this->errors[] = 'Could not read pathinfo.';

			return false;
		}

		$extension 	= isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : false;
		$imagename 	= isset($pathinfo['filename']) ? $pathinfo['filename'] : false;
		$newpath 	= false;

		if($original)
		{
			$image 	= glob($this->originalFolder . "$imagename.*");
			if(isset($image[0]))
			{
				$newpath = 'media/original/' . basename($image[0]);
			}
		}
		elseif($live)
		{
			$image 	= glob($this->liveFolder . "$imagename.*");
			if(isset($image[0]))
			{
				$newpath = 'media/live/' . basename($image[0]);
			}
		}

		return $newpath;

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