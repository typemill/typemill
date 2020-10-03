<?php

namespace Typemill\Extensions;

use Typemill\Extensions\ParsedownExtension;

class TwigMarkdownExtension extends \Twig_Extension
{
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('markdown', array($this, 'renderMarkdown' ))
		];
	}
		
	public function renderMarkdown($markdown)
	{		
		$parsedown = new ParsedownExtension();
		
		$markdownArray = $parsedown->text($markdown);
		
		return $parsedown->markup($markdownArray);
	}
}