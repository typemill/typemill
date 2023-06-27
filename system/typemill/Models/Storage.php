<?php

namespace Typemill\Models;

class Storage
{
	public $error 					= false;

	protected $basepath 			= false;

	protected $tmpFolder 			= false;

	protected $originalFolder 		= false;

	protected $liveFolder 			= false;

	protected $thumbsFolder 		= false;

	protected $customFolder 		= false;

	protected $fileFolder 			= false;

	protected $contentFolder 		= false;

	protected $dataFolder 			= false;

	protected $cacheFolder 			= false;

	protected $settingsFolder 		= false;

	protected $themeFolder 			= false;

	protected $pluginFolder 		= false;

	protected $translationFolder 	= false;

	protected $systemSettings 		= false;
 
	public function __construct()
	{
		$this->basepath 			= getcwd() . DIRECTORY_SEPARATOR;

		$this->tmpFolder 			= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

		$this->originalFolder 		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR;

		$this->liveFolder  			= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR;

		$this->thumbsFolder			= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR;

		$this->customFolder			= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR;

		$this->fileFolder 			= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
	
		$this->contentFolder 		= $this->basepath . 'content';

		$this->dataFolder  			= $this->basepath . 'data';

		$this->cacheFolder 			= $this->basepath . 'cache';

		$this->settingsFolder 		= $this->basepath . 'settings';

		$this->pluginFolder 		= $this->basepath . 'plugins';

		$this->themeFolder 			= $this->basepath . 'themes';

		$this->translationFolder 	= $this->basepath . 'system' .  DIRECTORY_SEPARATOR . 'typemill' . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;
	
		$this->systemSettings 		= $this->basepath . 'system' .  DIRECTORY_SEPARATOR . 'typemill' .  DIRECTORY_SEPARATOR . 'settings';
	}

	public function getError()
	{
		return $this->error;
	}

	public function getFolderPath($location, $folder = NULL)
	{
		if(isset($this->$location))
		{
			$path = rtrim($this->$location, DIRECTORY_SEPARATOR);
			$path .= DIRECTORY_SEPARATOR;
			if($folder && $folder != '')
			{
				$folder = trim($folder, DIRECTORY_SEPARATOR);
				$path .= $folder . DIRECTORY_SEPARATOR; 
			}
#			echo '<pre>';
#			echo $path;

			return $path;
		}

		$this->error = "We could not find a folderPath for $location";
		return false;
	}

	public function checkFolder($location, $folder)
	{
		$folderpath = $this->getFolderPath($location, $folder);

		if(!is_dir($folderpath) OR !is_writable($folderpath))
		{
			$this->error = "The folder $folderpath does not exist or is not writable.";

			return false;
		}

		return true;
	}

	public function createFolder($location, $folder)
	{
		$folderpath = $this->getFolderPath($location, $folder);

		if(is_dir($folderpath))
		{
			return true;
		}

		if(!mkdir($folderpath, 0755, true))
		{
			$this->error = "Could not create folder $folderpath.";

			return false;
		}

		return true;
	}

	public function checkFile($location, $folder, $filename)
	{
		$filepath = $this->getFolderPath($location, $folder) . $filename;

		if(!file_exists($filepath))
		{
			$this->error = "The file $filepath does not exist.";

			return false;
		}

		return true;
	}

	public function getFile($location, $folder, $filename, $method = NULL)
	{
		if($this->checkFile($location, $folder, $filename))
		{
			$filepath = $this->getFolderPath($location) . $folder . DIRECTORY_SEPARATOR . $filename;

			$fileContent = file_get_contents($filepath);
		
			# use unserialise or json_decode
			if($method && is_callable($method))
			{
				$fileContent = $method($fileContent);
			}

			return $fileContent;
		}

		return false;
	}

	public function getFileTime($location, $folder, $filename)
	{
		$filepath = $this->getFolderPath($location, $folder) . $filename;

		if(!file_exists($filepath))
		{
			$this->error = "The file $filepath does not exist.";

			return false;
		}

		return date("Y-m-d",filemtime($filepath));
	}

	public function writeFile($location, $folder, $filename, $data, $method = NULL)
	{
		# CLEAN EVERYTHING UP FUNCTION
		$folder 	= trim($folder, DIRECTORY_SEPARATOR);
		$folder 	= ($folder == '') ? '' : $folder . DIRECTORY_SEPARATOR;
		$filename 	= trim($filename, DIRECTORY_SEPARATOR);

		if(!$this->checkFolder($location, $folder))
		{
			if(!$this->createFolder($location, $folder))
			{
				return false;
			}
		}

		$filepath = $this->getFolderPath($location) . $folder . $filename;
			
		$openfile = @fopen($filepath, "w");
		if(!$openfile)
		{
			$this->error = "Could not open and read the file $filepath";

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
			$this->error = "Could not write to the file $filepath";

			return false;
		}

		fclose($openfile);

		return true;
	}

	public function renameFile($location, $folder, $oldname, $newname)
	{
		$folder = trim($folder, DIRECTORY_SEPARATOR);

		$oldFilePath = $this->getFolderPath($location) . $folder . DIRECTORY_SEPARATOR . $oldname;
		$newFilePath = $this->getFolderPath($location) . $folder . DIRECTORY_SEPARATOR . $newname;

		if($oldFilePath != $newFilePath)
		{
			if(!file_exists($oldFilePath))
			{
				return false;
			}

			if(!rename($oldFilePath, $newFilePath))
			{
				return false;
			}
		}
		
		return true;
	}

	public function deleteFile($location, $folder, $filename)
	{
		if($this->checkFile($location, $folder, $filename))
		{
			$filepath = $this->getFolderPath($location) . $folder . DIRECTORY_SEPARATOR . $filename;

			if(unlink($filepath))
			{
				return true;
			}

			$this->error = "We found the file but could not delete $filepath";
		}

		return false;
	}

	# used to sort the navigation / files 
	public function moveContentFile($item, $folderPath, $index, $date = null)
	{
		$filetypes			= array('md', 'txt', 'yaml');
		
		# set new order as string
		$newOrder			= ($index < 10) ? '0' . $index : $index;

		$newPath 			= $this->contentFolder . $folderPath . DIRECTORY_SEPARATOR . $newOrder . '-' . $item->slug;

		if($item->elementType == 'folder')
		{
			$oldPath = $this->contentFolder . $item->path;

			if(@rename($oldPath, $newPath))
			{
				return true;
			}
			return false;
		}
		
		# create old path but without filetype
		$oldPath		= substr($item->path, 0, strpos($item->path, "."));
		$oldPath		= $this->contentFolder . $oldPath;

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

	public function getYaml($location, $folder, $filename)
	{
		$yaml = $this->getFile($location, $folder, $filename);
		
		if($yaml)
		{
			return \Symfony\Component\Yaml\Yaml::parse($yaml);
		}

		return false;
	}

	public function updateYaml($location, $folder, $filename, $contentArray)
	{
		$yaml = \Symfony\Component\Yaml\Yaml::dump($contentArray,6);
		if($this->writeFile($location, $folder, $filename, $yaml))
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

	public function publishFile($name)
	{
		$pathinfo = pathinfo($name);
		if(!$pathinfo)
		{
			$this->error = 'Could not read pathinfo.';

			return false;
		}

		$filename = $pathinfo['filename'] . '.' . $pathinfo['extension'];
		$filepath = $this->tmpFolder . $filename;

		if(!file_exists($this->tmpFolder . $filename))
		{
			$this->error = "We did not find the file in the tmp-folder or could not read it.";
			return false;
		}

		$success = rename($this->tmpFolder . $filename, $this->fileFolder . $filename);
		
		if($success === true)
		{
			# return true;
			return 'media/files/' . $filename;
		}

		return false;
	}

	public function publishImage($name, $noresize = false)
	{
		$pathinfo = pathinfo($name);
		if(!$pathinfo)
		{
			$this->error = 'Could not read pathinfo.';

			return false;
		}

		$extension 	= isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : false;
		$imagename 	= isset($pathinfo['filename']) ? $pathinfo['filename'] : false;

		$imagesInTmp = glob($this->tmpFolder . "*$imagename.*"); 
		if(empty($imagesInTmp) OR !$imagesInTmp)
		{
			$this->error = "We did not find the image in the tmp-folder or could not read it.";
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

					$result = rename($imagepath, $this->originalFolder . $filename);
				
					if($noresize)
					{
						$result = copy($this->originalFolder . $filename, $this->liveFolder . $filename);
						$extension = pathinfo($this->originalFolder . $filename, PATHINFO_EXTENSION);
					}
				
					if(!$result)
					{
						$this->error = "We could not store the original image";
					}
				
					break;
				case 'live':
					if($noresize)
					{
						break;
					}
					if(!rename($imagepath, $this->liveFolder . $filename))
					{
						$this->error = "We could not store the live image to the live folder";
					}
					break;
				case 'thumbs':
					if(!rename($imagepath, $this->thumbsFolder . $filename))
					{
						$this->error = "We could not store the thumb to the thumb folder";
					}
					break;
			}
		}

		if(!$this->error)
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
			$this->error = 'Could not read pathinfo.';

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


	public function getStorageInfoBREAK($item)
	{
		if(isset($this->$item))
		{
			return $this->$item;
		}
		return false;
	}

	public function checkFolderBREAK($folder)
	{
		$folderpath = $this->basepath . $folder;

		if(!is_dir($folderpath) OR !is_writable($folderpath))
		{
			$this->error = "The folder $folder does not exist or is not writable.";

			return false;
		}

		return true;
	}

	public function createFolderBREAK($folder)
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

	public function checkFileBREAK($folder, $filename)
	{
		if(!file_exists($this->basepath . $folder . DIRECTORY_SEPARATOR . $filename))
		{
			$this->error = "The file $filename in folder $folder does not exist.";

			return false;
		}

		return true;
	}

	public function writeFileBREAK($folder, $filename, $data, $method = NULL)
	{
		echo '<pre>';
		var_dump($folder);
		die();

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

	public function getFileBREAK($folder, $filename, $method = NULL)
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

	public function renameFileBREAK($folder, $oldname, $newname)
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

	public function deleteFileBREAK($folder, $filename)
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

	# used to sort the navigation / files 
	public function moveContentFileBREAK($item, $folderPath, $index, $date = null)
	{
		$filetypes			= array('md', 'txt', 'yaml');
		
		# set new order as string
		$newOrder			= ($index < 10) ? '0' . $index : $index;

		$newPath 			= $this->contentFolder . $folderPath . DIRECTORY_SEPARATOR . $newOrder . '-' . $item->slug;

		if($item->elementType == 'folder')
		{
			$oldPath = $this->contentFolder . $item->path;

			if(@rename($oldPath, $newPath))
			{
				return true;
			}
			return false;
		}
		
		# create old path but without filetype
		$oldPath		= substr($item->path, 0, strpos($item->path, "."));
		$oldPath		= $this->contentFolder . $oldPath;

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
	
	*/
}