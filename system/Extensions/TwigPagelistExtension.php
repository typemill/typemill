<?php

namespace Typemill\Extensions;

use Typemill\Models\Folder;

class TwigPagelistExtension extends \Twig_Extension
{
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('getPageList', array($this, 'getList' ))
		];
	}

	public function getList($folderContentDetails, $url)
	{
		$pagelist = Folder::getItemForUrlFrontend($folderContentDetails, $url);

		return $pagelist;
	}
}