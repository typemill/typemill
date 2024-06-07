<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Typemill\Extensions\ParsedownExtension;

class TwigMarkdownExtension extends AbstractExtension
{
	protected $dispatcher;

	protected $settings;

	protected $baseurl;
	
	public function __construct($baseurl, $settings, $dispatcher)
	{
		$this->dispatcher = $dispatcher;

		$this->settings = $settings;

		$this->baseurl = $baseurl;
	}

	public function getFunctions()
	{
		return [
			new TwigFunction('markdown', array($this, 'renderMarkdown' ))
		];
	}
		
	public function renderMarkdown($markdown)
	{
		$parsedown = new ParsedownExtension($this->baseurl, $this->settings, $this->dispatcher);

		$markdownArray = $parsedown->text($markdown);
		
		return $parsedown->markup($markdownArray);
	}
}