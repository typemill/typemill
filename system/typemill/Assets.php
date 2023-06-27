<?php

namespace Typemill;

use Typemill\Models\ProcessImage;

# this class is available to the container and to all plugins
class Assets
{
	public $baseUrl;
	
	public function __construct($baseUrl)
	{
		$this->baseUrl			= $baseUrl;
		$this->JS 				= array();
		$this->CSS 				= array();
		$this->inlineJS			= array();
		$this->inlineCSS		= array();
		$this->editorJS 		= array();
		$this->editorCSS 		= array();
		$this->editorInlineJS 	= array();
		$this->svgSymbols		= array();
		$this->meta 			= array();
		$this->imageUrl 		= false;
		$this->imageFolder 		= 'original';
	}

	public function setUri($uri)
	{
		$this->uri = $uri;
	}

	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	public function image($url)
	{
		$this->imageUrl = $url;
		return $this;
	}

	public function resize($width,$height)
	{
		$pathinfo		= pathinfo($this->imageUrl);
		$extension 		= strtolower($pathinfo['extension']);
		$imageName 		= $pathinfo['filename'];

		$desiredSizes 	= ['custom' => []];

		$resize = '-';

		if(is_int($width) && $width < 10000)
		{
			$resize .= $width;
			$desiredSizes['custom']['width'] = $width;
		}

		$resize .= 'x';

		if(is_int($height) && $height < 10000)
		{
			$resize .= $height;
			$desiredSizes['custom']['height'] = $height;
		}

		$processImage 		= new ProcessImage($desiredSizes);

		$processImage->checkFolders('images');

		$imageNameResized 	= $imageName . $resize;
		$imagePathResized 	= $processImage->customFolder . $imageNameResized . '.' . $extension;
		$imageUrlResized 	= 'media/custom/' . $imageNameResized . '.' . $extension;

		if(!file_exists( $imagePathResized ))
		{
			# if custom version does not exist, use original version for resizing
			$imageFolder 	= ($this->imageFolder == 'original') ? $processImage->originalFolder : $processImage->customFolder;

			$imagePath 		= $imageFolder . $pathinfo['basename'];
			
			$resizedImage 	= $processImage->generateSizesFromImageFile($imageUrlResized, $imagePath);
			
			$savedImage		= $processImage->saveImage($processImage->customFolder, $resizedImage['custom'], $imageNameResized, $extension);
			
			if(!$savedImage)
			{
				# return old image url without resize
				return $this;
			}
		}
		# set folder to custom, so that the next method uses the correct (resized) version
		$this->imageFolder = 'custom';

		$this->imageUrl = $imageUrlResized;
		return $this;
	}

	public function grayscale()
	{
		$pathinfo		= pathinfo($this->imageUrl);
		$extension 		= strtolower($pathinfo['extension']);
		$imageName 		= $pathinfo['filename'];

		$processImage 	= new ProcessImage([]);

		$processImage->checkFolders('images');

		$imageNameGrayscale	= $imageName . '-grayscale';
		$imagePathGrayscale	= $processImage->customFolder . $imageNameGrayscale . '.' . $extension;
		$imageUrlGrayscale 	= 'media/custom/' . $imageNameGrayscale . '.' . $extension;

		if(!file_exists( $imagePathGrayscale ))
		{
			# if custom-version does not exist, use live-version for grayscale-manipulation.
			$imageFolder 	= ($this->imageFolder == 'original') ? $processImage->liveFolder : $processImage->customFolder;
			
			$imagePath 		= $imageFolder . $pathinfo['basename'];
			
			$grayscaleImage	= $processImage->grayscale($imagePath, $extension);

			$savedImage		= $processImage->saveImage($processImage->customFolder, $grayscaleImage, $imageNameGrayscale, $extension);
			
			if(!$savedImage)
			{
				# return old image url without resize
				return $this;
			}
		}

		# set folder to custom, so that the next method uses the correct (resized) version
		$this->imageFolder = 'custom';

		$this->imageUrl = $imageUrlGrayscale;
		return $this;
	}

	public function src()
	{
		# when we finish it, we shoud reset all settings
		$imagePath 				= $this->baseUrl . '/' . $this->imageUrl;
		$this->imageUrl 		= false;
		$this->imageFolder 		= 'original';

		return $imagePath;
	}

	public function addCSS($CSS)
	{
		$CSSfile = $this->getFileUrl($CSS);
		
		if($CSSfile)
		{
			$this->CSS[] = '<link rel="stylesheet" href="' . $CSSfile . '" />';
		}
	}
		
	public function addInlineCSS($CSS)
	{
		$this->inlineCSS[] = '<style>' . $CSS . '</style>';
	}
	
	public function addJS($JS)
	{
		$JSfile = $this->getFileUrl($JS);
		
		if($JSfile)
		{
			$this->JS[] = '<script src="' . $JSfile . '"></script>';
		}
	}

	public function addInlineJS($JS)
	{
		$this->inlineJS[] = '<script>' . $JS . '</script>';
	}

	public function activateVue()
	{
		$vueUrl = '<script src="' . $this->baseUrl . '/system/author/js/vue.min.js"></script>';
		if(!in_array($vueUrl, $this->JS))
		{
			$this->JS[] = $vueUrl;
		}
	}

	public function activateAxios()
	{
		$axiosUrl = '<script src="' . $this->baseUrl . '/system/author/js/axios.min.js"></script>';
		if(!in_array($axiosUrl, $this->JS))
		{
			$this->JS[] = $axiosUrl;

			$axios = '<script>const myaxios = axios.create({ baseURL: \'' . $this->baseUrl . '\' });</script>';
			$this->JS[] = $axios;
		}
	}
	
	public function activateTachyons()
	{
		$tachyonsUrl = '<link rel="stylesheet" href="' . $this->baseUrl . '/system/author/css/tachyons.min.css" />';
		if(!in_array($tachyonsUrl, $this->CSS))
		{
			$this->CSS[] = $tachyonsUrl;
		}
	}

	public function addSvgSymbol($symbol)
	{
		$this->svgSymbols[] = $symbol;
	}

	# add JS to enhance the blox-editor in author area
	public function addEditorJS($JS)
	{
		$JSfile = $this->getFileUrl($JS);
		
		if($JSfile)
		{
			$this->editorJS[] = '<script src="' . $JSfile . '"></script>';
		}
	}

	public function addEditorInlineJS($JS)
	{
		$this->editorInlineJS[] = '<script>' . $JS . '</script>';
	}

	public function addEditorCSS($CSS)
	{
		$CSSfile = $this->getFileUrl($CSS);
		
		if($CSSfile)
		{
			$this->editorCSS[] = '<link rel="stylesheet" href="' . $CSSfile . '" />';
		}
	}

	public function addMeta($key,$meta)
	{
		$this->meta[$key] = $meta;
	}

	public function renderEditorJS()
	{
		return implode("\n", $this->editorJS) . implode("\n", $this->editorInlineJS);
	}

	public function renderEditorCSS()
	{
		return implode("\n", $this->editorCSS);
	}

	public function renderCSS()
	{
		return implode("\n", $this->CSS) . implode("\n", $this->inlineCSS);
	}
	
	public function renderJS()
	{
		return implode("\n", $this->JS) . implode("\n", $this->inlineJS);
	}

	public function renderSvg()
	{
		return implode('', $this->svgSymbols);
	}

	public function renderMeta()
	{
		$metaLines = '';
		foreach($this->meta as $meta)
		{
			$metaLines .= "\n";
			$metaLines .= $meta;
		}
		return $metaLines;
	}
	/**
	 * Checks, if a string is a valid internal or external ressource like js-file or css-file
	 * @params $path string
	 * @return string or false 
	 */
	public function getFileUrl($path)
	{
		# check system path of file without parameter for fingerprinting
		$internalFile = __DIR__ . '/../../plugins' . strtok($path, "?");
		
		if(file_exists($internalFile))
		{
			return $this->baseUrl . '/plugins' . $path;
		}
		
		return $path;
		
		if(fopen($path, "r"))
		{
			return $path;
		}
		
		return false;		
	}
}