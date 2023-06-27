<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Typemill\Extensions\ParsedownExtension;

class TwigMarkdownExtension extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction('markdown', array($this, 'renderMarkdown' ))
		];
	}
		
	public function renderMarkdown($markdown)
	{
		$parsedown = new ParsedownExtension();
		
		$markdownArray = $parsedown->text($markdown);
		
		return $parsedown->markup($markdownArray);
	}
}