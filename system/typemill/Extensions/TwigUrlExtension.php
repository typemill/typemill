<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;

class TwigUrlExtension extends AbstractExtension
{	
	protected $urlinfo;

	public function __construct($urlinfo)
	{
		$this->urlinfo 		= $urlinfo;
	}

	public function getFunctions()
	{
		return [
			new \Twig\TwigFunction('base_url', array($this, 'baseUrl' )),
			new \Twig\TwigFunction('current_url', array($this, 'currentUrl' )),
			new \Twig\TwigFunction('current_path', array($this, 'currentPath' ))
		];
	}
	
	public function baseUrl()
	{
		return $this->urlinfo['baseurl'];
	}

	public function currentUrl()
	{
		return $this->urlinfo['currenturl'];
	}

	public function currentPath()
	{
		return $this->urlinfo['route'];
	}
}