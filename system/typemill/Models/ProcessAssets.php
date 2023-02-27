<?php

namespace Typemill\Models;

use Typemill\Models\Folder;

class ProcessAssets
{
	# holds the path to the temporary image folder
	public $basepath = false;

	public $tmpFolder = false;

	public $errors 	= [];

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
}