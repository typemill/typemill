<?php

namespace Typemill\Models;

use Typemill\Models\Folder;
use Typemill\Models\SvgSanitizer;
use Typemill\Static\Slug;

class Media
{ 
	public $errors 					= [];

	protected $basepath 			= false;

	protected $tmpFolder 			= false;

	protected $extension 			= false;

	protected $filename 			= false;

	protected $filetype 			= false;

	protected $filedata 			= false;

	protected $allowedExtensions 	= ['png' => true, 'jpg' => true, 'jpeg' => true, 'webp' => true];

	protected $animated 			= false;

	protected $resizable 			= true;

	protected $sizes  				= [];

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

		if(!isset($fileParts[0]) OR !isset($fileParts[1]))
		{
			$this->errors[] = 'Could not decode image or file, probably not a base64 encoding.';

			return false;
		}

		$type 				= explode("/", $fileParts[0]);
		$this->filetype		= strtolower($type[1]);
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

	public function is_dir_empty($dir) 
	{
		return (count(scandir($dir)) == 2);
	}


	#############################
	#     FILE HANDLING 		#
	#############################

	public function storeFile($file, $name)
	{
		$this->clearTempFolder();

		$this->setPathInfo($name);

		$this->decode($file);

		if($this->extension == "svg")
		{
			$svg = new SvgSanitizer();
			
			$loaded = $svg->loadSVG($this->filedata);
			if($loaded === false)
			{
				$this->errors[] = 'We could not load the svg file, it is probably corrupted.';
				return false;
			}

			$svg->sanitize();
			$sanitized 	= $svg->saveSVG();
			if($sanitized === false)
			{
				$this->errors[] = 'We could not create a sanitized version of the svg, it probably has invalid content.';
				return false;
			}

			$this->filedata = $sanitized;
		}
		

		$fullpath = $this->getFullPath();

		if($this->filedata !== false && file_put_contents($fullpath, $this->filedata))
		{
			$size = filesize($this->getFullPath());
			$size = $this->formatSizeUnits($size);

			$title = str_replace('-', ' ', $this->filename);
			$title = $title . ' (' . strtoupper($this->extension) . ', ' . $size .')';

			return [
				'title' 		=> $title, 
				'name' 			=> $this->filename, 
				'extension' 	=> $this->extension, 
				'size' 			=> $size, 
				'url' 			=> 'media/files/' . $this->getFullName()
			];
		}

		return false;
	}


	#############################
	#     IMAGE HANDLING 		#
	#############################

	public function prepareImage($image, $name)
	{
		# change clear tmp folder and delete only old ones
		$this->clearTempFolder();
		#$this->checkFolders('image');
		$this->decode($image);
		$this->setPathInfo($name);
		$this->checkAllowedExtension();

		if(empty($this->errors))
		{
			return true;
		}

		return false;
	}

	public function storeOriginalToTmp()
	{
		# $this->saveName();
		$this->saveOriginal();

		if(empty($this->errors))
		{
			return true;
		}

		return false;		
	}

	public function storeRenditionsToTmp($sizes)
	{
		# transform image-stream into image
		$image 	= $this->createImage();
		
		$originalsize = $this->getImageSize($image);

		foreach($sizes as $destinationfolder => $desiredsize)
		{
			$desiredsize = $this->calculateSize($originalsize, $desiredsize);

			$resizedImage = $this->resizeImage($image, $desiredsize, $originalsize);

			$this->saveResizedImage($resizedImage, $destinationfolder, $this->extension);

			imagedestroy($resizedImage);
		}

		imagedestroy($image);

		if(empty($this->errors))
		{
			return true;
		}

		return false;
	}

	# add an allowed image extension like svg
	public function addAllowedExtension(string $extension)
	{
		$this->allowedExtensions[$extension] = true;
	}

	# force an image type like webp
	public function setExtension(string $extension)
	{
		$this->extension = $extension;
	}

	public function checkAllowedExtension()
	{
		if(!isset($this->allowedExtensions[$this->extension]))
		{
			$this->errors[] = 'Images with this extension are not allowed.';

			return false;
		}

		if($this->extension == "svg")
		{
			$svg = new SvgSanitizer();
			
			$loaded = $svg->loadSVG($this->filedata);
			if($loaded === false)
			{
				$this->errors[] = 'We could not load the svg file, it is probably corrupted.';
				return false;
			}

			$svg->sanitize();
			$sanitized 	= $svg->saveSVG();
			if($sanitized === false)
			{
				$this->errors[] = 'We could not create a sanitized version of the svg, it probably has invalid content.';
				return false;
			}

			$this->filedata = $sanitized;
		}

		return true;
	}

	# check if image should not be resized (animated gif and svg)
	public function isResizable()
	{
		if($this->filetype == 'gif' && $this->detectAnimatedGif())
		{
			$this->resizable = false;
		}

		if($this->filetype == 'svg+xml')
		{
			$this->resizable = false;
		}

		return $this->resizable;
	}

	public function detectAnimatedGif()
	{
		$is_animated = preg_match('#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', $this->filedata);
		if ($is_animated == 1)
		{
			$this->animated = true;
		}

		return $this->animated;
	}

	# save the original image to temp folder
	public function saveOriginal($destinationfolder = 'ORIGINAL')
	{
		$path = $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.' . $this->extension;

		$result = file_put_contents($path, $this->filedata);
		if($result === false)
		{
			$this->errors[] = 'could not store the image in the temporary folder';			
		}
	}

	# save the original image for all sizes/folders
	public function saveOriginalForAll()
	{
		$this->saveOriginal('LIVE');
		$this->saveOriginal('THUMBS');

		if(empty($this->errors))
		{
			return true;
		}
		return false;
	}

	public function createImage()
	{
		return imagecreatefromstring($this->filedata);
	}

	public function getImageSize($image)
	{
		return ['width' => imagesx($image), 'height' => imagesy($image)];
	}

	public function calculateSize(array $originalsize, array $desiredsize)
	{
		# if desired size is bigger than the actual image, then drop the desired sizes and use the actual image size instead
		if($desiredsize['width'] > $originalsize['width'])
		{
			return $originalsize;
		}
		
		if(!isset($desiredsize['height']))
		{
			$resizeFactor				= $originalsize['width'] / $desiredsize['width'];
			$desiredsize['height']		= round( ($originalsize['height'] / $resizeFactor), 0);
		}

		return $desiredsize;
	}

	public function resizeImage($image, array $desired, array $original)
	{
		# resize
		$ratio 	= max($desired['width']/$original['width'], $desired['height']/$original['height']);
		$h 		= $desired['height'] / $ratio;
		$x 		= ($original['width'] - $desired['width'] / $ratio) / 2;
		$y 		= ($original['height'] - $desired['height'] / $ratio) / 2;
		$w 		= $desired['width'] / $ratio;

		$resizedImage = imagecreatetruecolor($desired['width'], $desired['height']);

		# preserve transparency
		if($this->extension == "gif" or $this->extension == "png" or $this->extension == "webp")
		{
			imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
			imagealphablending($resizedImage, false);
			imagesavealpha($resizedImage, true);
		}

		imagecopyresampled($resizedImage, $image, 0, 0, $x, $y, $desired['width'], $desired['height'], $w, $h);

		return $resizedImage;
	}

	public function saveResizedImage($resizedImage, string $destinationfolder, string $extension)
	{
		$destinationfolder = strtoupper($destinationfolder);		

		switch($extension)
		{
			case "png":
				$storedImage = imagepng( $resizedImage, $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.png', 9 );
				break;
			case "gif":
				$storedImage = imagegif( $resizedImage, $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.gif' );
				break;
			case "webp":
				$storedImage = imagewebp( $resizedImage, $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.webp', 80);
				break;
			case "jpg":
			case "jpeg":
				$storedImage = imagejpeg( $resizedImage, $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.' . $extension, 80);
				break;
			default:
				$storedImage = false;
		}

		if(!$storedImage)
		{
			$failedImage = $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.' . $extension;

			$this->errors[] = "Could not store the resized version $failedImage";

			return false;
		}

		return true;
	}







	# REFACTOR IF NEEDED 

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