<?php
namespace Typemill\Models;

use \URLify;

class ProcessAssets
{
	# holds the path to the baseFolder
	protected $baseFolder;

	# holds the path to the mediaFolder
	protected $mediaFolder;

	# holds the path to the temporary image folder
	protected $tmpFolder;

	# holds the path where original images are stored
	protected $originalFolder;

	# holds the path where images for frontend use are stored
	protected $liveFolder;

	# holds the folder where the thumbs for the media library are stored
	protected $thumbFolder;

	# holds the folder where the thumbs for the media library are stored
	public $fileFolder;

	# holds the desired sizes for image resizing
	protected $desiredSizes;

	public function __construct($desiredSizes = NULL)
	{
		$this->baseFolder		= getcwd() . DIRECTORY_SEPARATOR;

		$this->mediaFolder		= $this->baseFolder . 'media' . DIRECTORY_SEPARATOR;

		$this->tmpFolder		= $this->mediaFolder . 'tmp' . DIRECTORY_SEPARATOR;

		$this->originalFolder	= $this->mediaFolder . 'original' . DIRECTORY_SEPARATOR;

		$this->liveFolder 		= $this->mediaFolder . 'live' . DIRECTORY_SEPARATOR;

		$this->thumbFolder 		= $this->mediaFolder . 'thumbs' . DIRECTORY_SEPARATOR;

		$this->fileFolder 		= $this->mediaFolder . 'files' . DIRECTORY_SEPARATOR;

		$this->desiredSizes 	= $desiredSizes;
	}

	public function checkFolders($forassets = null)
	{

		$folders = [$this->mediaFolder, $this->tmpFolder, $this->fileFolder];
		
		if($forassets == 'images')
		{
			$folders = [$this->mediaFolder, $this->tmpFolder, $this->originalFolder, $this->liveFolder, $this->thumbFolder];
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

	public function is_dir_empty($dir) 
	{
		return (count(scandir($dir)) == 2);
	}

	public function setFileName($originalname, $type, $overwrite = null)
	{
		$pathinfo			= pathinfo($originalname);
		
		$this->extension 	= strtolower($pathinfo['extension']);
		$this->filename 	= URLify::filter(iconv(mb_detect_encoding($pathinfo['filename'], mb_detect_order(), true), "UTF-8", $pathinfo['filename']));

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

	public function getName()
	{
		return $this->filename;
	}

	public function getExtension()
	{
		return $this->extension;
	}

	public function getFullName()
	{
		return $this->filename . '.' . $this->extension;
	}

	public function clearTempFolder()
	{
		$files 		= scandir($this->tmpFolder);
		$result		= true;
		
		foreach($files as $file)
		{
			if (!in_array($file, array(".","..")))
			{			
				$filelink = $this->tmpFolder . $file;
				if(!unlink($filelink))
				{
					$success = false;
				}	
			}
		}
		
		return $result;
	}

	public function cleanupLiveFolder()
	{		
		# delete all old thumbs mlibrary in live folder
		foreach(glob($this->liveFolder . '*mlibrary*') as $filename)
		{
			unlink($filename);
		}

		return true;
	}	
	
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