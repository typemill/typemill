<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Typemill\Models\Meta;

class TwigMetaExtension extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction('getPageMeta', array($this, 'getMeta' ))
		];
	}
		
	public function getMeta($settings, $item)
	{

		$meta = new Meta();

		$metadata = $meta->getMetaData($item);

		if(!$metadata OR $metadata['meta']['title'] == '' OR $metadata['meta']['description'] == '')
		{
			$metadata = $meta->addMetaDefaults($metadata, $item, $settings['author']);
		}

		return $metadata;
	}
}