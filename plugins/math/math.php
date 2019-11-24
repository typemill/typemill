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

		# add math to the blox editor configuration

		$this->addEditorJS('/math/public/math.js');
		$this->addSvgSymbol('<symbol id="icon-omega" viewBox="0 0 32 32">
			<title>omega</title>
			<path d="M22 28h8l2-4v8h-12v-6.694c4.097-1.765 7-6.161 7-11.306 0-6.701-4.925-11.946-11-11.946s-11 5.245-11 11.946c0 5.144 2.903 9.541 7 11.306v6.694h-12v-8l2 4h8v-1.018c-5.863-2.077-10-7.106-10-12.982 0-7.732 7.163-14 16-14s16 6.268 16 14c0 5.875-4.137 10.905-10 12.982v1.018z"></path>
			</symbol>');
	}

}