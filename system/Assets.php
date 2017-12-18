<?php

namespace Typemill;

class Assets
{
	protected $baseUrl;
	
	public function __construct($baseUrl)
	{
		$this->baseUrl		= $baseUrl;
		$this->JS 			= array();
		$this->CSS 			= array();
		$this->inlineJS		= array();
		$this->inlineCSS	= array();
	}
	
	public function addCSS(string $CSS)
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
	
	public function addJS(string $JS)
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
	
	public function renderCSS()
	{
		return implode('<br/>', $this->CSS) . implode('<br/>', $this->inlineCSS);
	}
	
	public function renderJS()
	{
		return implode('<br/>', $this->JS) . implode('<br/>', $this->inlineJS);
	}

	/**
	 * Checks, if a string is a valid internal or external ressource like js-file or css-file
	 * @params $path string
	 * @return string or false 
	 */
	public function getFileUrl(string $path)
	{				
		$internalFile = __DIR__ . '/../plugins' . $path;
		
		if(file_exists($internalFile))
		{
			return $this->baseUrl . '/plugins' . $path;
		}
		
		if(fopen($path, "r"))
		{
			return $path;
		}
		
		return false;		
	}
}