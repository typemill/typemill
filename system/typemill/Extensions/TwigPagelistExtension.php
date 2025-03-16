<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigPagelistExtension extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction('getPageList', array($this, 'getList' ))
		];
	}

	public function getList($folderContentDetails, $url, $result = NULL)
	{
		foreach($folderContentDetails as $key => $item)
		{
			# set item active, needed to move item in navigation
			if($item->urlRelWoF === $url)
			{
				$item->active = true;
				$result = $item;
			}
			elseif($item->elementType === "folder")
			{
				$result = $this->getList($item->folderContent, $url, $result);
			}
		}

		return $result;
	}
}