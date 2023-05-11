<?php

namespace Typemill\Models;

use Typemill\Models\Folder;
use Typemill\Static\Slug;


class ProcessAssets
{ 
	public $errors 		= [];

	public $basepath 	= false;

	public $tmpFolder 	= false;

	public $extension 	= false;

	public $filename 	= false;

	public $filetype 	= false;

	public $filedata 	= false;

	public function __construct()
	{
		ini_set('memory_limit', '512M');

		$this->basepath 		= getcwd() . DIRECTORY_SEPARATOR;
	
		$this->tmpFolder		= $this->basepath . 'media' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
	}

	public function clearTempFolder()
	{
		$files 		= scandir($this->tmpFolder);
		$now 		= time();
		$result		= true;
		
		foreach($files as $file)
		{
			if (!in_array($file, array(".","..")))
			{
				$filelink = $this->tmpFolder . $file;
				if(file_exists($filelink))
				{
					$filetime = filemtime($filelink);
					if($now - $filetime > 1800)
					{
						if(!unlink($filelink))
						{
							$result = false;
						}			
					}
				}
			}
		}
		
		return $result;
	}

	# set the pathinfo (name and extension) and slugify a unique name if option to overwrite existing files is false
	public function setPathInfo(string $name)
	{
		$pathinfo			= pathinfo($name);
		if(!$pathinfo)
		{
			$this->errors[] = 'Could not read pathinfo.';

			return false;
		}

		$this->extension 	= isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : false;
		$this->filename 	= Slug::createSlug($pathinfo['filename']);

		if(!$this->extension OR !$this->filename)
		{
			$this->errors[] = 'Extension or filename are missing.';

			return false;
		}

		return true;
	}

	public function decode(string $file)
	{
		$fileParts 		= explode(";base64,", $file);
		$fileType		= explode("/", $fileParts[0]);
		$fileData		= base64_decode($fileParts[1]);

		$fileParts 		= explode(";base64,", $file);

		if(!isset($fileParts[0]) OR !isset($fileParts[1]))
		{
			$this->errors[] = 'Could not decode image or file, probably not a base64 encoding.';

			return false;
		}

		$type 				= explode("/", $fileParts[0]);
		$this->filetype		= strtolower($fileType[0]);
		$this->filedata		= base64_decode($fileParts[1]);

		return true;
	}	

	public function getExtension()
	{
		return $this->extension;
	}

	public function getFiletype()
	{
		return $this->filetype;
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	public function getFullName()
	{
		return $this->filename . '.' . $this->extension;
	}

	public function getFiledata()
	{
		return $this->filedata;
	}

	public function getFullPath()
	{
		return $this->tmpFolder . $this->filename . '.' . $this->extension;
	}

    public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
	}



/*
	public function checkFolders($forassets = null)
	{

		$folders = [$this->mediaFolder, $this->tmpFolder, $this->fileFolder];
		
		if($forassets == 'images')
		{
			$folders = [$this->mediaFolder, $this->tmpFolder, $this->originalFolder, $this->liveFolder, $this->thumbFolder, $this->customFolder];
		}

		foreach($folders as $folder)
		{
			if(!file_exists($folder) && !is_dir( $folder ))
			{
				if(!mkdir($folder, 0755, true))
				{
					return false;
				}
				if($folder == $this->thumbFolder)
				{
					# cleanup old systems
					$this->cleanupLiveFolder();

					# generate thumbnails from live folder
					$this->generateThumbs();
				}
			}
			elseif(!is_writeable($folder) OR !is_readable($folder))
			{
				return false;
			} 

			# check if thumb-folder is empty, then generate thumbs from live folder
			if($folder == $this->thumbFolder && $this->is_dir_empty($folder))
			{				
				# cleanup old systems
				$this->cleanupLiveFolder();

				# generate thumbnails from live folder
				$this->generateThumbs();
			}
		}
		return true;
	}
*/

	public function is_dir_empty($dir) 
	{
		return (count(scandir($dir)) == 2);
	}

/*
	public function setFileName($originalname, $type, $overwrite = NULL)
	{
		$pathinfo			= pathinfo($originalname);
		$this->extension 	= isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : null;
		$this->filename 	= Folder::createSlug($pathinfo['filename']);

		$filename = $this->filename;

		# check if file name is 
		if(!$overwrite)
		{
			$suffix = 1;

			$destination = $this->liveFolder;
			if($type == 'file')
			{
				$destination = $this->fileFolder;
			}

			while(file_exists($destination . $filename . '.' . $this->extension))
			{
				$filename = $this->filename . '-' . $suffix;
				$suffix++;
			}
		}

		$this->filename = $filename;

		return true;
	}
*/

/*
	public function getName()
	{
		return $this->filename;
	}

	public function setExtension($extension)
	{
		$this->extension = $extension;
	}

	public function getExtension()
	{
		return $this->extension;
	}

	public function getFullName()
	{
		return $this->filename . '.' . $this->extension;
	}

*/

/*
	public function cleanupLiveFolder()
	{		
		# delete all old thumbs mlibrary in live folder
		foreach(glob($this->liveFolder . '*mlibrary*') as $filename)
		{
			unlink($filename);
		}

		return true;
	}	
*/
	
	public function findPagesWithUrl($structure, $url, $result)
	{
		foreach ($structure as $key => $item)
		{
			if($item->elementType == 'folder')
			{
				$result = $this->findPagesWithUrl($item->folderContent, $url, $result);
			}
			else
			{
				$live = getcwd() . DIRECTORY_SEPARATOR . 'content' . $item->pathWithoutType . '.md';
				$draft = getcwd() . DIRECTORY_SEPARATOR . 'content' . $item->pathWithoutType . '.txt';

				# check live first
				if(file_exists($live))
				{
					$content = file_get_contents($live);
					
					if (stripos($content, $url) !== false)
					{
						$result[] = $item->urlRelWoF;
					}
					# if not in live, check in draft
					elseif(file_exists($draft))
					{
						$content = file_get_contents($draft);
						
						if (stripos($content, $url) !== false)
						{
							$result[] = $item->urlRelWoF;
						}
					}
				}
			}
		}
		return $result;
	}

}