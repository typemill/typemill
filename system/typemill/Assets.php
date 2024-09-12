<?php

namespace Typemill;

use Typemill\Models\Media;
use Typemill\Models\Meta;
use Typemill\Models\StorageWrapper;

# this class is available to the container and to all plugins
class Assets
{
	public $baseUrl;

	public $CSS;

	public $inlineCSS;

	public $JS;

	public $inlineJS;

	public $bloxConfigJS;

	public $bloxConfigInlineJS;

	public $editorJS; # deprecated

	public $editorCSS; # deprecated

	public $editorInlineJS; # deprecated

	public $svgSymbols;

	public $meta;

	public $imageUrl;

	public $imageFolder;

	public function __construct($baseUrl)
	{
		$this->baseUrl				= $baseUrl;
		$this->CSS 					= array();
		$this->inlineCSS			= array();
		$this->JS 					= array();
		$this->inlineJS				= array();
		$this->bloxConfigJS 		= array();
		$this->bloxConfigInlineJS 	= array();
		$this->editorJS 			= array(); # deprecated
		$this->editorCSS 			= array(); # deprecated
		$this->editorInlineJS 		= array(); # deprecated
		$this->svgSymbols			= array();
		$this->meta 				= array();
		$this->imageUrl 			= false;
		$this->imageFolder 			= 'originalFolder';
	}

	public function setUri($uri)
	{
		$this->uri = $uri;
	}

	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
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

	public function addBloxConfigJS($JS)
	{
		$JSfile = $this->getFileUrl($JS);
		
		if($JSfile)
		{
			$this->bloxConfigJS[] = '<script src="' . $JSfile . '"></script>';
		}
	}

	public function addBloxConfigInlineJS($JS)
	{
		$this->bloxConfigInlineJS[] = '<script>' . $JS . '</script>';
	}

	public function activateVue()
	{
		$vueUrl = '<script src="' . $this->baseUrl . '/system/typemill/author/js/vue.js"></script>';
		if(!in_array($vueUrl, $this->JS))
		{
			$this->JS[] = $vueUrl;
		}
	}

	public function activateAxios()
	{
		$axiosUrl = '<script src="' . $this->baseUrl . '/system/typemill/author/js/axios.min.js"></script>';
		if(!in_array($axiosUrl, $this->JS))
		{
			$this->JS[] = $axiosUrl;

			$axios = '<script>const tmaxios = axios.create({ baseURL: \'' . $this->baseUrl . '\' });</script>';
			$this->JS[] = $axios;
		}
	}
	
	public function activateTachyons()
	{
		die('Hi from asset class, Tachyons not available in Typemill v2');		
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

	public function renderCSS()
	{
		return implode("\n", $this->CSS) . implode("\n", $this->inlineCSS);
	}
	
	public function renderJS()
	{
		return implode("\n", $this->JS) . implode("\n", $this->inlineJS);
	}

	public function renderBloxConfigJS()
	{
		return implode("\n", $this->bloxConfigJS) . implode("\n", $this->bloxConfigInlineJS);
	}

	public function renderSvg()
	{
		return implode('', $this->svgSymbols);
	}

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

	/**********************
	 *   META FEATURES  *
	**********************/

	public function addMeta($key,$meta)
	{
		$this->meta[$key] = $meta;
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

	/**********************
	 * IMAGE MANIPULATION *
	**********************/

	public function image($url)
	{
		# image url is passed with twig-function
		$this->imageUrl = $url;

		$this->media 	= new Media();
		
		return $this;
	}

	public function resize($width, $height)
	{
		$this->imageUrl = $this->media->createCustomSize($this->imageUrl, $width, $height);

		return $this;
	}

	public function grayscale()
	{
		$this->imageUrl = $this->media->createGrayscale($this->imageUrl);

		return $this;
	}

	public function src()
	{
		# create absolute image url
		$absImageUrl = $this->baseUrl . '/' . $this->imageUrl;
		
		# reset image url
		$this->imageUrl = false;

		return $absImageUrl;
	}

	/******************
	 *   DEPRECATED   *
	 * ****************/

	# deprecated, not in use
	public function addEditorJS($JS)
	{
		$JSfile = $this->getFileUrl($JS);
		
		if($JSfile)
		{
			$this->editorJS[] = '<script src="' . $JSfile . '"></script>';
		}
	}

	# deprecated, not in use
	public function addEditorInlineJS($JS)
	{
		$this->editorInlineJS[] = '<script>' . $JS . '</script>';
	}

	# deprecated, not in use
	public function addEditorCSS($CSS)
	{
		$CSSfile = $this->getFileUrl($CSS);
		
		if($CSSfile)
		{
			$this->editorCSS[] = '<link rel="stylesheet" href="' . $CSSfile . '" />';
		}
	}

	# deprecated, not in use
	public function renderEditorJS()
	{
		return implode("\n", $this->editorJS) . implode("\n", $this->editorInlineJS);
	}

	# deprecated, not in use
	public function renderEditorCSS()
	{
		return implode("\n", $this->editorCSS);
	}
}