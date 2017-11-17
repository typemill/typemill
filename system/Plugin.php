<?php

namespace Typemill;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class Plugin implements EventSubscriberInterface
{
	
	protected $app;
	
	protected $container;

    /**
     * Constructor.
     *
     * @param string $name
     * @param Grav   $grav
     * @param Config $config
     */
    public function __construct($container, $app)
    {
		$this->container 	= $container;
		$this->app			= $app;
    }

	protected function getRoute()
	{
		return $this->container['request']->getUri();
	}
				
	protected function getPath()
	{
		$route = $this->container['request']->getUri();
		return $route->getPath();
	}
	
	protected function getDispatcher($dispatcher)
	{
		return $dispatcher;
	}
	
}
