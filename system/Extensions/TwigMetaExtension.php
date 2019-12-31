<?php

namespace Typemill\Extensions;

use Typemill\Models\WriteYaml;

class TwigMetaExtension extends \Twig_Extension
{
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('getPageMeta', array($this, 'getMeta' ))
		];
	}
		
	public function getMeta($settings, $item)
	{
		$write = new WriteYaml();
		
		$meta = $write->getPageMeta($settings, $item);
		
		return $meta;
	}
}