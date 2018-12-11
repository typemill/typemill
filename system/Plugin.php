<?php

namespace Typemill;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Typemill\Models\Fields;

abstract class Plugin implements EventSubscriberInterface
{	
	protected $container;

    /**
     * Constructor
     *
     */
	 
    public function __construct($container)
    {
		$this->container 	= $container;
    }
	
	protected function getSettings()
	{
		return $this->container->get('settings');
	}
	
	protected function getPluginSettings($plugin)
	{
		return $this->container->get('settings')['plugins'][$plugin];
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
	
	protected function getTwig()
	{
		return $this->container['view'];
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
	
	protected function generateForm($pluginName)
	{
		$fieldsModel = new Fields();
		
		$pluginDefinitions = \Typemill\Settings::getObjectSettings('plugins', $pluginName);
				
		if(isset($pluginDefinitions['frontend']['fields']))
		{
			# get all the fields and prefill them with the dafault-data, the user-data or old input data
			$fields = $fieldsModel->getFields($userSettings = false, 'plugins', $pluginName, $pluginDefinitions, 'frontend');

			# use the field-objects to generate the html-fields
			
		}
	}
}