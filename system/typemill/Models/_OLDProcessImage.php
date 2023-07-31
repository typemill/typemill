<?php

namespace Typemill\Models;

#use Slim\Http\UploadedFile;
use Typemill\Static\Slug;

class ProcessImage extends ProcessAssets
{
	protected $allowedExtensions 	= ['png' => true, 'jpg' => true, 'jpeg' => true, 'webp' => true];

	protected $animated 			= false;

	protected $resizable 			= true;

	protected $sizes  				= [];

	public function prepareImage($image, $name)
	{
		die('use processAssets instead of processImages');

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

		die('use processAssets instead of processImages');

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
		die('use processAssets instead of processImages');


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
		die('use processAssets instead of processImages');
		$this->allowedExtensions[$extension] = true;
	}

	# force an image type like webp
	public function setExtension(string $extension)
	{
		die('use processAssets instead of processImages');
		$this->extension = $extension;
	}

	public function checkAllowedExtension()
	{
		die('use processAssets instead of processImages');
		if(!isset($this->allowedExtensions[$this->extension]))
		{
			$this->errors[] = 'Images with this extension are not allowed.';

			return false;
		}

		return true;
	}

	# check if image should not be resized (animated gif and svg)
	public function isResizable()
	{
		die('use processAssets instead of processImages');
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
		die('use processAssets instead of processImages');
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
		die('use processAssets instead of processImages');
		$path = $this->tmpFolder . $destinationfolder . '+' . $this->filename . '.' . $this->extension;
		
		if(!file_put_contents($path, $this->filedata))
		{
			$this->errors[] = 'could not store the image in the temporary folder';			
		}
	}

	# save the original image for all sizes/folders
	public function saveOriginalForAll()
	{
		die('use processAssets instead of processImages');
		$this->saveOriginal('LIVE');
		$this->saveOriginal('THUMBS');
	}

	public function createImage()
	{
		die('use processAssets instead of processImages');
		return imagecreatefromstring($this->filedata);
	}

	public function getImageSize($image)
	{
		die('use processAssets instead of processImages');
		return ['width' => imagesx($image), 'height' => imagesy($image)];
	}

	public function calculateSize(array $originalsize, array $desiredsize)
	{
		die('use processAssets instead of processImages');
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
		die('use processAssets instead of processImages');
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
		die('use processAssets instead of processImages');
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













	# in use ??
	public function deleteImageWithName($name)
	{
		die("processImage model deleteImageWithName please check method.");
		# e.g. delete $name = 'logo...';

		$name = basename($name);

		if($name != '' && !in_array($name, array(".","..")))
		{
			foreach(glob($this->liveFolder . $name) as $file)
			{
				unlink($file);
			}
			foreach(glob($this->originalFolder . $name) as $file)
			{
				unlink($file);
			}
			foreach(glob($this->thumbFolder . $name) as $file)
			{
				unlink($file);
			}
		}
	}

	# in use ??
	public function copyImage($name,$sourcefolder,$targetfolder)
	{
		die("processImage model copyImage please check method.");

		copy($sourcefolder . $name, $targetfolder . $name);
	}















	/**
	 * Moves the uploaded file to the upload directory. Only used for settings / NON VUE.JS uploads
	 *
	 * @param string $directory directory to which the file is moved
	 * @param UploadedFile $uploadedFile file uploaded file to move
	 * @return string filename of moved file
	 */
	public function moveUploadedImage(UploadedFile $uploadedFile, $overwrite = false, $name = false, $folder = NULL)
	{
		die('use processAssets instead of processImages');
		$this->setFileName($uploadedFile->getClientFilename(), 'file');
		
		if($name)
		{
			$this->setFileName($name . '.' . $this->extension, 'file', $overwrite);
		}

		if(!$folder)
		{
			$folder = $this->liveFolder;
		}	

	    $uploadedFile->moveTo($folder . $this->getFullName());

	    return $this->getFullName();
	}	

	









/*
	# save the image name as txt to temp folder
	public function saveName()
	{
		$path = $this->tmpFolder . $this->filename . '.txt';

		if(!fopen($path, "w"))
		{
			$this->errors[] = 'could not store the filename in the temporary folder';
		}
	}
*/




	public function generateThumbs()
	{
		die('use processAssets instead of processImages');
		# generate images from live folder to 'tmthumbs'
		$liveImages 	= scandir($this->liveFolder);

		$result = false;

		foreach ($liveImages as $key => $name)
		{
			if (!in_array($name, array(".","..")))
			{
				$result = $this->generateThumbFromImageFile($name);
			}
		}
		return $result;
	}

	public function generateThumbFromImageFile($filename)
	{
		die('use processAssets instead of processImages');
		$this->setFileName($filename, 'image', $overwrite = true);

		$image 			= $this->createImageFromPath($this->liveFolder . $filename, $this->extension);

		$originalSize 	= $this->getImageSize($image);

		$thumbSize		= $this->desiredSizes['thumbs'];

		$thumb 			= $this->imageResize($image, $originalSize, ['thumbs' => $thumbSize ], $this->extension);

		$saveImage 		= $this->saveImage($this->thumbFolder, $thumb['thumbs'], $this->filename, $this->extension);
		if($saveImage)
		{
			return true;
		}
		return false;
	}

	# filename and imagepath can be a tmp-version after upload.
	public function generateSizesFromImageFile($filename, $imagePath)
	{
		die('use processAssets instead of processImages');
		$this->setFileName($filename, 'image');

		$image 			= $this->createImageFromPath($imagePath, $this->extension);

		$originalSize 	= $this->getImageSize($image);

		$resizedImages 	= $this->imageResize($image, $originalSize, $this->desiredSizes, $this->extension);

		return $resizedImages;
	}

	public function grayscale($imagePath, $extension)
	{
		die('use processAssets instead of processImages');
		$image 	= $this->createImageFromPath($imagePath, $extension);

		imagefilter($image, IMG_FILTER_GRAYSCALE);

		return $image;
	}

	public function createImageFromPath($imagePath, $extension)
	{
		die('use processAssets instead of processImages');
		switch($extension)
		{
			case 'gif': $image = imagecreatefromgif($imagePath); break;
			case 'jpg' :
			case 'jpeg': $image = imagecreatefromjpeg($imagePath); break;
			case 'png': $image = imagecreatefrompng($imagePath); break;
			case 'webp': $image = imagecreatefromwebp($imagePath); break;
			default: return 'image type not supported';
		}
		
		return $image;		
	}
}