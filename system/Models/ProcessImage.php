<?php
namespace Typemill\Models;

class ProcessImage
{
	public function createImage(string $image, array $desiredSizes)
	{
		# fix error from jpeg-library
		ini_set ('gd.jpeg_ignore_warning', 1);
		error_reporting(E_ALL & ~E_NOTICE);
		
		# clear temporary folder
		$this->clearTempFolder();
		
		# decode the image from base64-string
		$imageDecoded	= $this->decodeImage($image);
		$imageData		= $imageDecoded["image"];
		$imageType		= $imageDecoded["type"];
		
		# transform image-stream into image
		$image 			= imagecreatefromstring($imageData);
		
		# get the size of the original image
		$imageSize 		= $this->getImageSize($image);
		
		# check the desired sizes and calculate the height, if not set
		$desiredSizes	= $this->setHeight($imageSize, $desiredSizes);
		
		# resize the images
		$resizedImages	= $this->imageResize($image, $imageSize, $desiredSizes, $imageType);
		
		$basePath		= getcwd() . DIRECTORY_SEPARATOR . 'media';
		$tmpFolder		= $basePath . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

		$this->saveOriginal($tmpFolder, $imageData, 'original', $imageType);
	
		if($imageType == "gif" && $this->detectAnimatedGif($imageData))
		{
			$this->saveOriginal($tmpFolder, $imageData, 'live', $imageType);
			
			return true;
		}
		
		# temporary store resized images
		foreach($resizedImages as $key => $resizedImage)
		{
			$this->saveImage($tmpFolder, $resizedImage, $key, $imageType);
		}
		
		return true;
	}
	
	public function detectAnimatedGif($image_file_contents)
	{
		$is_animated = preg_match('#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', $image_file_contents);
		if ($is_animated == 1)
		{
			return true;
		}
		return false;
	}
	
	public function publishImage(array $desiredSizes, $name = false)
	{
		/* get images from tmp folder */
		$basePath		= getcwd() . DIRECTORY_SEPARATOR . 'media';
		$tmpFolder		= $basePath . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
		$originalFolder	= $basePath . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR;
		$liveFolder		= $basePath . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR;

		if(!file_exists($originalFolder)){ mkdir($originalFolder, 0774, true); }
		if(!file_exists($liveFolder)){ mkdir($liveFolder, 0774, true); }

		$name 			= $name ? $name : uniqid();
		
		$files 			= scandir($tmpFolder);
		$success		= true;
		
		foreach($files as $file)
		{
			if (!in_array($file, array(".","..")))
			{			
				$tmpfilename 	= explode(".", $file);
				
				if($tmpfilename[0] == 'original')
				{
					$success = rename($tmpFolder . $file, $originalFolder . $name . '-' . $file);
				}
				else
				{
					$success = rename($tmpFolder . $file, $liveFolder . $name . '-' . $file);
				}
			}
		}
		
		if($success)
		{
			return 'media/live/' . $name . '-live.' . $tmpfilename[1];
		}
		
		return false;
	}
	
	public function decodeImage(string $image)
	{
        $imageParts 	= explode(";base64,", $image);
        $imageType		= explode("/", $imageParts[0]);
		$imageData		= base64_decode($imageParts[1]);
	
		if ($imageData !== false)
		{
			return array("image" => $imageData, "type" => $imageType[1]);
		}
		
		return false;
	}

	public function getImageSize($image)
	{
		$width = imagesx($image);
		$height = imagesy($image);
		return array('width' => $width, 'height' => $height);
	}
	
	public function setHeight(array $imageSize, array $desiredSizes)
	{
		foreach($desiredSizes as $key => $desiredSize)
		{
			if($desiredSize['width'] > $imageSize['width'])
			{
				$desiredSizes[$key] = $imageSize;
				continue;
			}
			
			if(!isset($desiredSize['height']))
			{
				$resizeFactor					= $imageSize['width'] / $desiredSize['width'];
				$desiredSizes[$key]['height']	= round( ($imageSize['height'] / $resizeFactor), 0);
			}
		}
		return $desiredSizes;
	}

	public function imageResize($imageData, array $imageSize, array $desiredSizes, $imageType)
	{
		$copiedImages			= array();
		$source_aspect_ratio 	= $imageSize['width'] / $imageSize['height'];
				
		foreach($desiredSizes as $key => $desiredSize)
		{
			$desired_aspect_ratio 	= $desiredSize['width'] / $desiredSize['height'];

			if ( $source_aspect_ratio > $desired_aspect_ratio )
			{
				# when source image is wider
				$temp_height 	= $desiredSize['height'];
				$temp_width 	= ( int ) ($desiredSize['height'] * $source_aspect_ratio);
				$temp_width 	= round($temp_width, 0);
			}
			else
			{
				# when source image is similar or taller
				$temp_width 	= $desiredSize['width'];
				$temp_height 	= ( int ) ($desiredSize['width'] / $source_aspect_ratio);
				$temp_height 	= round($temp_height, 0);
			}

			# Create a temporary GD image with desired size
			$temp_gdim = imagecreatetruecolor( $temp_width, $temp_height );

			if ($imageType == "gif")
			{
				$transparent_index 	= imagecolortransparent($imageData);
				imagepalettecopy($imageData, $temp_gdim);
				imagefill($temp_gdim, 0, 0, $transparent_index);
				imagecolortransparent($temp_gdim, $transparent_index);
				imagetruecolortopalette($temp_gdim, true, 256);
			}
			elseif($imageType == "png")
			{ 
				imagealphablending($temp_gdim, false);
				imagesavealpha($temp_gdim, true);
				$transparent = imagecolorallocatealpha($temp_gdim, 255, 255, 255, 127);
				imagefilledrectangle($temp_gdim, 0, 0, $temp_width, $temp_height, $transparent);
			}
			
			# resize image
			imagecopyresampled(
				$temp_gdim,
				$imageData,
				0, 0,
				0, 0,
				$temp_width, $temp_height,
				$imageSize['width'], $imageSize['height']
			);

			$copiedImages[$key]		= $temp_gdim;
			
			/*
			
			# Copy cropped region from temporary image into the desired GD image
			$x0 = ( $temp_width - $desiredSize['width'] ) / 2;
			$y0 = ( $temp_height - $desiredSize['height'] ) / 2;

			$desired_gdim = imagecreatetruecolor( $desiredSize['width'], $desiredSize['height'] );

			if ($imageType == "gif")
			{
				imagepalettecopy($temp_gdim, $desired_gdim);
				imagefill($desired_gdim, 0, 0, $transparent_index);
				imagecolortransparent($desired_gdim, $transparent_index);
				imagetruecolortopalette($desired_gdim, true, 256);
			}
			elseif($imageType == "png")
			{
				imagealphablending($desired_gdim, false);
				imagesavealpha($desired_gdim,true);
				$transparent = imagecolorallocatealpha($desired_gdim, 255, 255, 255, 127);
				imagefilledrectangle($desired_gdim, 0, 0, $desired_size['with'], $desired_size['height'], $transparent);
			}

			imagecopyresampled(
				$desired_gdim,
				$temp_gdim,
				0, 0,
				0, 0,
				$x0, $y0,
				$desiredSize['width'], $desiredSize['height']				
			);
			$copiedImages[$key]		= $desired_gdim;
			
			*/
		}
		return $copiedImages;
	}
	
	public function saveOriginal($folder, $image, $name, $type)
	{
		if(!file_exists($folder))
		{
			mkdir($folder, 0774, true);
		}
		
		$path = $folder . $name . '.' . $type;
		
		file_put_contents($path, $image);
	}

	public function saveImage($folder, $image, $name, $type)
	{
		if(!file_exists($folder))
		{
			mkdir($folder, 0774, true);
		}
		
		if($type == "png")
		{
			$result = imagepng( $image, $folder . '/' . $name . '.png' );
		}
		elseif($type == "gif")
		{
			$result = imagegif( $image, $folder . '/' . $name . '.gif' );
		}
		else
		{
			$result = imagejpeg( $image, $folder . '/' . $name . '.jpeg' );
			$type = 'jpeg';
		}
		
		imagedestroy($image);
		
		if($result)
		{
			return $name . '.' . $type;
		}
		
		return false;
	}
	
	public function clearTempFolder()
	{
		$folder		= getcwd() . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

		if(!file_exists($folder))
		{
			mkdir($folder, 0774, true);
			return true;
		}		
		
		$files 		= scandir($folder);
		$result		= true;
		
		foreach($files as $file)
		{
			if (!in_array($file, array(".","..")))
			{			
				$filelink = $folder . $file;
				if(!unlink($filelink))
				{
					$success = false;
				}	
			}
		}
		
		return $result;
	}
	
	public function deleteImage($name)
	{
		$baseFolder		= getcwd() . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;
		$original		= $baseFolder . 'original' . DIRECTORY_SEPARATOR . $name . '*';
		$live			= $baseFolder . 'live' . DIRECTORY_SEPARATOR . $name . '*';
		$success 		= true;
		
		foreach(glob($original) as $image)
		{
			if(!unlink($image))
			{
				$success = false;
			}
		}
		
		foreach(glob($live) as $image)
		{
			if(!unlink($image))
			{
				$success = false;
			}
		}
		
		return $success;
	}
}


?>