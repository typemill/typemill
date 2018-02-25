<?php

namespace Plugins\Disqus;

use \Typemill\Plugin;

class Disqus extends Plugin
{
	protected $settings;

    public static function getSubscribedEvents()
    {
		return [];
    }
	
	public function onSettingsLoaded($settings)
	{
		$this->settings = $settings->getData();
	}
	
	public function onTwigLoaded()
	{
		/* get Twig Instance and add the cookieconsent template-folder to the path */
		$twig 	= $this->getTwig();					
		$loader = $twig->getLoader();
		$loader->addPath(__DIR__ . '/templates');

		$analyticSettings = $this->settings['settings']['plugins']['analytics'];
	
		/* fetch the template, render it with twig and add javascript with settings */
		if($analyticSettings['tool'] == 'piwik')
		{
			$this->addInlineJS($twig->fetch('/piwikanalytics.twig', $this->settings));
		}
		elseif($analyticSettings['tool'] == 'google')
		{
			$this->addJS('https://www.googletagmanager.com/gtag/js?id=' . $analyticSettings['google_id']);
			$this->addInlineJS($twig->fetch('/googleanalytics.twig', $analyticSettings));
		}
	}
}