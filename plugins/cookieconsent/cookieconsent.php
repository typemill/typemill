<?php

namespace Plugins\CookieConsent;

use \Typemill\Plugin;

class CookieConsent extends Plugin
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
		/* add external CSS and JavaScript */
		$this->addCSS('/cookieconsent/public/cookieconsent.min.css');
		$this->addJS('/cookieconsent/public/cookieconsent.min.js');

		/* get Twig Instance and add the cookieconsent template-folder to the path */
		$twig 	= $this->getTwig();					
		$loader = $twig->getLoader();
		$loader->addPath(__DIR__ . '/templates');
	
		/* fetch the template, render it with twig and add it as inline-javascript */
		$this->addInlineJS($twig->fetch('/cookieconsent.twig', $this->settings));
	}
}