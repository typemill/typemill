<?php

namespace Typemill;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class Plugin implements EventSubscriberInterface
{	
	private $container;

    /**
     * Constructor
     *
     */
	 
    public function __construct($container)
    {
		$this->container 	= $container;
    }

	protected function getRoute()
	{
		return $this->container['request']->getUri();
	}
	
	protected function getPath()
	{
		return $this->container['request']->getUri()->getPath();
	}
	
	protected function getDispatcher()
	{
		return $this->$dispatcher;
	}
	
	protected function addTwigGlobal($name, $class)
	{
		$this->container->view->getEnvironment()->addGlobal($name, $class);
	}
	
	protected function addTwigFilter($name, $filter)
	{
		$filter = new \Twig_SimpleFilter($name, $filter);
		$this->container->view->getEnvironment()->addFilter($filter);
	}
	
	protected function addTwigFunction($name, $function)
	{
		$function = new \Twig_SimpleFunction($name, $function);
		$this->container->view->getEnvironment()->addFunction($function);		
	}
	
	protected function addJS($JS)
	{
		$this->container->assets->addJS($JS);
	}

	protected function addInlineJS($JS)
	{
		$this->container->assets->addInlineJS($JS);
	}
	
	protected function addCSS($CSS)
	{
		$this->container->assets->addCSS($CSS);		
	}
	
	protected function addInlineCSS($CSS)
	{
		$this->container->assets->addInlineCSS($CSS);		
	}
}