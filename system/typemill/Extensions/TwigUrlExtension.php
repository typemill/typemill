<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;

class TwigUrlExtension extends AbstractExtension
{	
	protected $uri;
	protected $basepath;
	protected $scheme;
	protected $authority;
	protected $protocol;

	public function __construct($uri, $basepath)
	{
		$this->uri 			= $uri;
		$this->basepath 	= $basepath;
		$this->scheme 		= $uri->getScheme();
		$this->authority 	= $uri->getAuthority();
		$this->protocol 	= ($this->scheme ? $this->scheme . ':' : '') . ($this->authority ? '//' . $this->authority : '');
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
		return $this->protocol . $this->basepath;
	}

	public function currentUrl()
	{
		return $this->protocol . $this->uri->getPath();
	}

	public function currentPath()
	{
		return $this->uri->getPath();
	}
}