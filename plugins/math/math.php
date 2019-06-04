<?php

namespace Plugins\Math;

use \Typemill\Plugin;

class Math extends Plugin
{
	protected $settings;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onSettingsLoaded'		=> 'onSettingsLoaded',		
			'onTwigLoaded' 			=> 'onTwigLoaded'
		);
    }
	
	public function onSettingsLoaded($settings)
	{
		$this->settings = $settings->getData();
	}	
	
	public function onTwigLoaded()
	{
		$mathSettings = $this->settings['settings']['plugins']['math'];
	
		if($mathSettings['tool'] == 'mathjax')
		{
			/* add external CSS and JavaScript */
			$this->addJS('https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/latest.js?config=TeX-MML-AM_CHTML');			
		}

		if($mathSettings['tool'] == 'katex')
		{
			$this->addJS('/math/public/katex.min.js');
			$this->addJS('/math/public/auto-render.min.js');
			$this->addCSS('/math/public/katex.min.css');

			/* initialize autorendering of page only in frontend */
			if (strpos($this->getPath(), 'tm/content') === false) 
			{
				$this->addInlineJs('renderMathInElement(document.body);');
			}
		}
	}
}