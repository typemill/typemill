<?php

namespace Typemill\Extensions;

use Typemill\Models\WriteMeta;

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
		$writeMeta = new WriteMeta();
		
		$meta = $writeMeta->getPageMeta($settings, $item);

		if(!$meta OR $meta['meta']['title'] == '' OR $meta['meta']['description'] == '')
		{
			# create path to the file
			$filePath	= $settings['rootPath'] . $settings['contentFolder'] . $item->path;
			
			# check if url is a folder and add index.md 
			if($item->elementType == 'folder')
			{
				$filePath 	= $filePath . DIRECTORY_SEPARATOR . 'index.md';
			}

			if(file_exists($filePath))
			{
				# get content
				$content = file_get_contents($filePath);

				# completes title and description or generate default meta values
				$meta = $writeMeta->completePageMeta($content, $settings, $item);
			}
		}
		
		return $meta;
	}
}