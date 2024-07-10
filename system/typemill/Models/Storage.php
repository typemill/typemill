<?php

namespace Typemill\Models;

use Typemill\Static\Helpers;
use Typemill\Static\Translations;

class Storage
{
	public $error 					= false;

	private $basepath 				= false;

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

	protected $themesFolder 		= false;

	protected $pluginsFolder 		= false;

	protected $translationFolder 	= false;

	protected $systemSettings 		= false;

	protected $isReadable 			= [];

	protected $isWritable 			= [];
 
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

		$this->pluginsFolder 		= $this->basepath . 'plugins';

		$this->themesFolder 		= $this->basepath . 'themes';

		$this->translationFolder 	= $this->basepath . 'system' .  DIRECTORY_SEPARATOR . 'typemill' . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;
	
		$this->systemSettings 		= $this->basepath . 'system' .  DIRECTORY_SEPARATOR . 'typemill' .  DIRECTORY_SEPARATOR . 'settings';
	
		$this->isWritable 			= [
										'tmpFolder' 		=> true, 
										'originalFolder'	=> true, 
										'liveFolder'		=> true, 
										'thumbsFolder'		=> true, 
										'customFolder'		=> true, 
										'fileFolder'		=> true, 
										'contentFolder' 	=> true,
										'dataFolder'		=> true, 
										'cacheFolder' 		=> true, 
										'settingsFolder'	=> true
									];
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

			# check if folder is no hack like "../"
			if($folder && $folder != '' && preg_match('/^(?:[\/\\a-z0-9_-]|\.(?!\.))+$/iD', $folder))
			{
				$folder = trim($folder, DIRECTORY_SEPARATOR);
				$path .= $folder . DIRECTORY_SEPARATOR; 
			}
			elseif($location == 'basepath')
			{
				# do not allow direct access to basepath files

				$this->error = Translations::translate('Access to basepath is not allowed.');
				
				return false;
			}

			return $path;
		}

		$this->error = Translations::translate('We could not find a folderPath for') . ' ' . $location;
		
		return false;
	}

	public function checkFolder($location, $folder = NULL)
	{
		$folderpath = $this->getFolderPath($location, $folder);

		if(!is_dir($folderpath) OR !is_writable($folderpath))
		{
			$this->error = $folderpath . ' ' . Translations::translate('does not exist or is not writable') . '.';

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
			$this->error = Translations::translate('Could not create folder') . ' ' . $folderpath;

			return false;
		}

		return true;
	}

	public function deleteFolder($location, $folder, $filename)
	{
		if(!isset($this->isWritable[$location]))
		{
			$this->error = Translations::translate('It is not allowed to write into') . ' ' . $location;

			return false;
		}

		$filepath = $this->getFolderPath($location, $folder) . $filename;

		if(is_dir($filepath))
		{
			if(rmdir($dir))
			{
				return true;
			}

			$this->error = Translations::translate('We found the folder but could not delete') . ' ' . $filepath;

			return false;
		}
		
		$this->error = $filepath . ' ' .Translations::translate('is not a folder') . '.';

		return false;
	}

	public function deleteContentFolder($filepath)
	{
		$filepath = $this->getFolderPath('contentFolder') . $filepath;

		if(is_dir($filepath))
		{
			if(rmdir($filepath))
			{
				return true;
			}

			$this->error = Translations::translate('We found the folder but could not delete it') . ' ' . $filepath;

			return false;
		}

		return true;
	}

	public function deleteContentFolderRecursive($folderpath)
	{
		$folderdir = $this->getFolderPath('contentFolder');

		if(!is_dir($folderdir . $folderpath))
		{
			$this->error = $folderpath . ' ' . Translations::translate('is not a directory');
			return false;
		}

		$filelist = array_diff(scandir($folderdir . $folderpath), array('..', '.'));
		if(!empty($filelist))
		{
			foreach($filelist as $filepath)
			{
				$fullfilepath = $folderdir . $folderpath . DIRECTORY_SEPARATOR . $filepath;
				if(is_dir($fullfilepath))
				{
					$this->deleteContentFolderRecursive($folderpath . DIRECTORY_SEPARATOR . $filepath);
				}
				else
				{
					if(!unlink($fullfilepath))
					{
						$this->error = Translations::translate('Could not delete file') . ' ' . $fullfilepath;

						return false;
					}
				}
			}
		}

		if(!rmdir($folderdir . $folderpath))
		{
			$this->error = Translations::translate('Could not delete folder') . ' ' . $folderpath;
			
			return false;
		}
		
		return true;
	}

	public function checkFile($location, $folder, $filename)
	{
		$filepath = $this->getFolderPath($location, $folder) . $filename;

		if(!file_exists($filepath))
		{
			$this->error = $filepath . ' ' . Translations::translate('does not exist');

			return false;
		}

		return true;
	}

	public function getFile($location, $folder, $filename, $method = NULL)
	{
		if($this->checkFile($location, $folder, $filename))
		{
			$filepath = $this->getFolderPath($location, $folder) . $filename;

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
			$this->error = $filepath . ' ' . Translations::translate('does not exist');

			return false;
		}

		return date("Y-m-d",filemtime($filepath));
	}

	public function writeFile($location, $folder, $filename, $data, $method = NULL)
	{
		if(!isset($this->isWritable[$location]))
		{
			$this->error = Translations::translate('It is not allowed to write into') . ' ' . $location;

			return false;
		}

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

		$filepath = $this->getFolderPath($location, $folder) . $filename;

		$openfile = @fopen($filepath, "w");
		if(!$openfile)
		{
			$this->error = Translations::translate('Could not open and read the file') . ' ' . $filepath;

			return false;
		}

		# serialize, json_decode
		if($method && is_callable($method))
		{
			$data = $method($data);
		}

		$writefile = fwrite($openfile, $data);
		if($writefile === false)
		{
			$this->error = Translations::translate('Could not write to the file') . ' ' . $filepath;

			return false;
		}

		fclose($openfile);

		return true;
	}

	public function renameFile($location, $folder, $oldname, $newname)
	{
		if(!isset($this->isWritable[$location]))
		{
			$this->error = Translations::translate('It is not allowed to write into') . ' ' . $location;

			return false;
		}

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
		if(!isset($this->isWritable[$location]))
		{
			$this->error = Translations::translate('It is not allowed to write into') . ' ' . $location;

			return false;
		}

		if($this->checkFile($location, $folder, $filename))
		{
			$filepath = $this->getFolderPath($location) . $folder . DIRECTORY_SEPARATOR . $filename;
	
			if(unlink($filepath))
			{
				return true;
			}

			$this->error = Translations::translate('We found the file but could not delete') . ' ' . $filepath;

			return false;
		}

		$this->error = Translations::translate('We did not find a file with that name');
		
		# we do not want to stop delete operation just because a file was not there, so return a message and true.
		return true;
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

			if(is_dir($oldPath))
			{
				if(@rename($oldPath, $newPath))
				{
					return true;
				}
				return false;
			}
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
		if(!isset($this->isWritable[$location]))
		{
			$this->error = Translations::translate('It is not allowed to write into') . ' ' . $location;

			return false;
		}

		$yaml = \Symfony\Component\Yaml\Yaml::dump($contentArray,6);
		if($this->writeFile($location, $folder, $filename, $yaml))
		{
			return true;
		}

		return false;
	}

	######################
	## 	  Timeout 		##
	######################

	public function timeoutIsOver($name, $timespan)
	{
		$location 	= 'cacheFolder';
		$folder 	= '';
		$filename 	= 'timer.yaml';

		// Get current timers from the YAML file, if it exists
		$timers = $this->getYaml($location, $folder, $filename) ?: [];

		$currentTime = time();
		$timeThreshold = $currentTime - $timespan;

		# Check if the name exists and if the timestamp is older than the current time minus the timespan
		if (!isset($timers[$name]) || !is_numeric($timers[$name]) || $timers[$name] <= $timeThreshold)
		{
			# If the name doesn't exist or the timestamp is older, update the timer
			$timers[$name] = $currentTime;

			# Update the YAML file with the new or updated timer
			$this->updateYaml($location, $folder, $filename, $timers);

			return true;
		}

		# If the name exists and the timestamp is not older, return false
		return false;
	}


	##################
	## 	  IMAGES 	##
	##################

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

	public function publishImage($name, $noresize = false)
	{
		$pathinfo = pathinfo($name);
		if(!$pathinfo)
		{
			$this->error = Translations::translate('Could not read pathinfo') . '.';

			return false;
		}

		$extension 	= isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : false;
		$imagename 	= isset($pathinfo['filename']) ? $pathinfo['filename'] : false;

		if(!$extension OR !$imagename)
		{
			$this->error = Translations::translate('Extension or name for image is missing') . '.';
			return false;
		}

		$imagesInTmp = glob($this->tmpFolder . "*$imagename.*"); 
		if(empty($imagesInTmp) OR !$imagesInTmp)
		{
			$this->error = Translations::translate('We did not find the image in the tmp-folder or could not read it') . '.';
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
						$this->error = Translations::translate('We could not store the original image') . '.';
					}
				
					break;
				case 'live':
					if($noresize)
					{
						break;
					}
					if(!rename($imagepath, $this->liveFolder . $filename))
					{
						$this->error = Translations::translate('We could not store the live image to the live folder');
					}
					break;
				case 'thumbs':
					if(!rename($imagepath, $this->thumbsFolder . $filename))
					{
						$this->error = Translations::translate('We could not store the thumb to the thumb folder');
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
			$this->error = Translations::translate('Could not read pathinfo');

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

	public function getImageList()
	{
		$thumbs 		= array_diff(scandir($this->thumbsFolder), array('..', '.'));
		$imagelist		= array();

		foreach ($thumbs as $key => $name)
		{
			$imagelist[] = [
				'name' 		=> $name,
				'timestamp'	=> filemtime($this->thumbsFolder . $name),
				'src_thumb'	=> 'media/thumbs/' . $name,
				'src_live'	=> 'media/live/' . $name,
			];
		}
		
		$imagelist = Helpers::array_sort($imagelist, 'timestamp', SORT_DESC);

		return $imagelist;
	}

	# get details from existing image for media library
	public function getImageDetails($name)
	{		
		$name = basename($name);

		if (!in_array($name, array(".","..")) && file_exists($this->liveFolder . $name))
		{
			$imageinfo 		= getimagesize($this->liveFolder . $name);

			if(!$imageinfo && pathinfo($this->liveFolder . $name, PATHINFO_EXTENSION) == 'svg')
			{
				$imagedetails = [
					'name' 		=> $name,
					'timestamp'	=> filemtime($this->liveFolder . $name),
					'bytes' 	=> filesize($this->liveFolder . $name),
					'width'		=> '---',
					'height'	=> '---',
					'type'		=> 'svg',
					'src_thumb'	=> 'media/thumbs/' . $name,
					'src_live'	=> 'media/live/' . $name,
				];
			}
			else
			{
				$imagedetails = [
					'name' 		=> $name,
					'timestamp'	=> filemtime($this->liveFolder . $name),
					'bytes' 	=> filesize($this->liveFolder . $name),
					'width'		=> $imageinfo[0],
					'height'	=> $imageinfo[1],
					'type'		=> $imageinfo['mime'],
					'src_thumb'	=> 'media/thumbs/' . $name,
					'src_live'	=> 'media/live/' . $name,
				];
			}

			return $imagedetails;
		}

		return false;
	}

	public function storeCustomImage($image, $extension, $imageName)
	{
		switch($extension)
		{
			case "png":
				$storedImage = imagepng( $image, $this->customFolder . $imageName . '.png', 9 );
				break;
			case "gif":
				$storedImage = imagegif( $image, $this->customFolder . $imageName . '.gif' );
				break;
			case "webp":
				$storedImage = imagewebp( $image, $this->customFolder . $imageName . '.webp', 80);
				break;
			case "jpg":
			case "jpeg":
				$storedImage = imagejpeg( $image, $this->customFolder . $imageName . '.' . $extension, 80);
				break;
			default:
				$storedImage = false;
		}

		if(!$storedImage)
		{
			$this->error = Translations::translate('Could not store the custom size of') . ' ' . $imageName;

			return false;
		}

		return true;
	}

	public function deleteImage($name)
	{
		# validate name 
		$name = basename($name);

		if(!file_exists($this->liveFolder . $name) OR !unlink($this->liveFolder . $name))
		{
			$this->error .= Translations::translate('We could not delete the live image.') . ' ';
		}

		if(!file_exists($this->thumbsFolder . $name) OR !unlink($this->thumbsFolder . $name))
		{
			$this->error .= Translations::translate('We could not delete the thumb image.') . ' ';
		}

		# delete custom images (resized and grayscaled) array_map('unlink', glob("some/dir/*.txt"));
		$pathinfo = pathinfo($name);

		foreach(glob($this->originalFolder . $pathinfo['filename'] . '\.*') as $image)
		{
			# you could check if extension is the same here
			if(!unlink($image))
			{
				$this->error = Translations::translate('We could not delete the original image in') . ' ' . $this->originalFolder;
			}
		}

		foreach(glob($this->customFolder . $pathinfo['filename'] . '\-*.' . $pathinfo['extension']) as $image)
		{
			# you could check if extension is the same here
			if(!unlink($image))
			{
				$this->error .= Translations::translate('we could not delete a custom image (grayscale or resized).') . ' ';
			}
		}
		
		if(!$this->error)
		{
			return true;
		}

		return false;
	}

	##################
	## 	  FILES 	##
	##################
 	
 	public function checkFileExists($filepath)
	{
		$pathinfo = pathinfo($filepath);
		if(!$pathinfo)
		{
			$this->error = Translations::translate('Could not read pathinfo');

			return false;
		}

		$filename 	= $pathinfo['filename'] . '.' . $pathinfo['extension'];
		$newpath 	= false;

		if($this->checkFile('fileFolder', '', $filename))
		{
			$newpath = 'media/files/' . $filename;
		}

		return $newpath;
	}

	public function publishFile($name)
	{
		$pathinfo = pathinfo($name);
		if(!$pathinfo)
		{
			$this->error = Translations::translate('Could not read pathinfo');

			return false;
		}

		$filename = $pathinfo['filename'] . '.' . $pathinfo['extension'];
		$filepath = $this->tmpFolder . $filename;

		if(!file_exists($this->tmpFolder . $filename))
		{
			$this->error = Translations::translate('We did not find the file in the tmp-folder or could not read it') . '.';
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

	public function getFileList()
	{
		$files 		= scandir($this->fileFolder);
		$filelist	= array();

		foreach ($files as $key => $name)
		{
			if (!in_array($name, array(".","..","filerestrictions.yaml")) && file_exists($this->fileFolder . $name))
			{
				$filelist[] = [
					'name' 		=> $name,
					'timestamp'	=> filemtime($this->fileFolder . $name),
					'bytes' 	=> filesize($this->fileFolder . $name),					
					'info'		=> pathinfo($this->fileFolder . $name),
					'url'		=> 'media/files/' . $name,
				];
			}
		}

		$filelist = Helpers::array_sort($filelist, 'timestamp', SORT_DESC);

		return $filelist;
	}

	public function deleteMediaFile($name)
	{
		# validate name 
		$name = basename($name);

		if(file_exists($this->fileFolder . $name) && unlink($this->fileFolder . $name))
		{
			return true;
		}

		return false;
	}

	public function deleteFileWithName($name)
	{
		# e.g. delete $name = 'logo';

		$name = basename($name);

		if($name != '' && !in_array($name, array(".","..")))
		{
			foreach(glob($this->fileFolder . $name) as $file)
			{
				unlink($file);
			}
		}
	}

	##################
	## 	 POST PAGES	##
	##################

	public function transformPagesToPosts($folder)
	{		
		$filetypes			= array('md', 'txt', 'yaml');
		$result 			= true;

		foreach($folder->folderContent as $page)
		{
			# create old filename without filetype
			$oldFile 	= $this->contentFolder . $page->pathWithoutType;

			# set default date
			$date 		= date('Y-m-d', time());
			$time		= date('H-i', time());

			$meta 		= $this->getYaml('contentFolder', '', $page->pathWithoutType . '.yaml');

			if($meta)
			{
				# get dates from meta
				if(isset($meta['meta']['manualdate'])){ $date = $meta['meta']['manualdate']; }
				elseif(isset($meta['meta']['created'])){ $date = $meta['meta']['created']; }
				elseif(isset($meta['meta']['modified'])){ $date = $meta['meta']['modified']; }

				# set time
				if(isset($meta['meta']['time']))
				{
					$time = $meta['meta']['time'];
				}
			}

			$datetime 	= $date . '-' . $time;
			$datetime 	= implode(explode('-', $datetime));
			$datetime	= substr($datetime,0,12);

			# create new file-name without filetype
			$newFile 	= $this->contentFolder . $folder->path . DIRECTORY_SEPARATOR . $datetime . '-' . $page->slug;

			foreach($filetypes as $filetype)
			{
				$oldFilePath = $oldFile . '.' . $filetype;
				$newFilePath = $newFile . '.' . $filetype;
							
				#check if file with filetype exists and rename
				if($oldFilePath != $newFilePath && file_exists($oldFilePath))
				{
					if(@rename($oldFilePath, $newFilePath))
					{
						$result = $result;
					}
					else
					{
						$this->error = "could not rename $oldFilePath to $newFilePath";
						$result = false;
					}
				}
			}
		}

		return $result;
	}

	public function transformPostsToPages($folder)
	{
		$filetypes			= array('md', 'txt', 'yaml');
		$index				= 0;
		$result 			= true;

		foreach($folder->folderContent as $page)
		{
			# create old filename without filetype
			$oldFile 	= $this->contentFolder . $page->pathWithoutType;

			$order 		= $index;

			if($index < 10)
			{
				$order = '0' . $index;
			}

			# create new file-name without filetype
			$newFile 	= $this->contentFolder . $folder->path . DIRECTORY_SEPARATOR . $order . '-' . $page->slug;

			foreach($filetypes as $filetype)
			{
				$oldFilePath = $oldFile . '.' . $filetype;
				$newFilePath = $newFile . '.' . $filetype;
				
				#check if file with filetype exists and rename
				if($oldFilePath != $newFilePath && file_exists($oldFilePath))
				{
					if(@rename($oldFilePath, $newFilePath))
					{
						$result = $result;
					}
					else
					{
						$this->error = "could not rename $oldFilePath to $newFilePath";
						$result = false;
					}
				}
			}

			$index++;
		}

		return $result;
	}
}