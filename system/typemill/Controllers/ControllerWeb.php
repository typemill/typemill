<?php

namespace Typemill\Controllers;

use DI\Container;
use Slim\Views\Twig;
use Typemill\Events\OnTwigLoaded;

class ControllerWeb extends Controller
{
	public function __construct(Container $container)
	{
		parent::__construct($container);

		echo '<br>add twig';

		$settings = $this->settings;

		$csrf = isset($_SESSION) ? $this->c->get('csrf') : false;

		$this->c->set('view', function() use ($settings, $csrf)
		{
			$twig = Twig::create(
				[
					# path to templates
					$settings['rootPath'] . $settings['authorFolder'],
					$settings['rootPath'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $settings['theme'],
				],
				[
					# settings
					'cache' => ( isset($settings['twigcache']) && $settings['twigcache'] ) ? $settings['rootPath'] . '/cache/twig' : false,
					'debug' => isset($settings['displayErrorDetails'])
				]
			);
			
			# placeholder for flash and errors, will be filled later with middleware
			$twig->getEnvironment()->addGlobal('errors', NULL);
			$twig->getEnvironment()->addGlobal('flash', NULL);

			# add extensions
			$twig->addExtension(new \Twig\Extension\DebugExtension());
			# $twig->addExtension(new \Nquire\Extensions\TwigUserExtension());
			if($csrf)
			{
				$twig->addExtension(new \Typemill\Extensions\TwigCsrfExtension($csrf));
			}

			return $twig;
		});

		$this->c->get('dispatcher')->dispatch(new OnTwigLoaded(false), 'onTwigLoaded');

	}
}