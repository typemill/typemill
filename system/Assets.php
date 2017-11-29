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
		$CSSpath = __DIR__ . '/../plugins' . $CSS;

		if(file_exists($CSSfile))
		{
			$CSSfile = $this->baseUrl . '/plugins' . $CSS;
			$this->CSS[] = '<link rel="stylesheet" href="' . $CSSfile . '" />';
		}
	}
	
	public function addInlineCSS($CSS)
	{
		$this->inlineCSS[] = '<style>' . $CSS . '</style>';
	}
	
	public function addJS(string $JS)
	{
		$JSpath = __DIR__ . '/../plugins' . $JS;
		
		if(file_exists($JSpath))
		{
			$JSfile = $this->baseUrl . '/plugins' . $JS;
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
}