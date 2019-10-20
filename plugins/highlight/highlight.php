<?php

namespace Plugins\Highlight;

use \Typemill\Plugin;

class Highlight extends Plugin
{
	protected $settings;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onTwigLoaded' 			=> 'onTwigLoaded'
		);
    }
	
	
	public function onTwigLoaded()
	{
		/* add external CSS and JavaScript */
		$this->addCSS('/highlight/public/default.css');
		$this->addJS('/highlight/public/highlight.pack.js');
	
		/* initialize the script */
		$this->addInlineJS('hljs.initHighlightingOnLoad();');
	}
}