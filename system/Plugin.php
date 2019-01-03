<?php

namespace Typemill;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Typemill\Models\Fields;
use Typemill\Models\WriteYaml;
use Typemill\Extensions\ParsedownExtension;

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
	
	protected function markdownToHtml($markdown)
	{
		$parsedown 		= new ParsedownExtension();
		
		$contentArray 	= $parsedown->text($markdown);
		$html			= $parsedown->markup($contentArray,false);
		
		return $html;
	}
	
	protected function getFormData($pluginName)
	{
		$flash = $this->container->flash->getMessages();
		if(isset($flash['formdata']))
		{
			$yaml 		= new Models\WriteYaml();
			$formdata 	= $yaml->getYaml('settings', 'formdata.yaml');
			$yaml->updateYaml('settings', 'formdata.yaml', '');
			
			if($flash['formdata'][0] == $pluginName && isset($formdata[$pluginName]))
			{
				return $formdata[$pluginName];
			}
		}
		return false;
	}
	
	protected function generateForm($pluginName)
	{
		$fieldsModel = new Fields();
		
		$pluginDefinitions 	= \Typemill\Settings::getObjectSettings('plugins', $pluginName);
		$settings 			= $this->getSettings();
		$buttonlabel		= isset($settings['plugins'][$pluginName]['button_label']) ? $settings['plugins'][$pluginName]['button_label'] : false;
		
		if(isset($pluginDefinitions['public']['fields']))
		{
			# add simple honeypot spam protection
			$pluginDefinitions['public']['fields']['personal-mail'] = ['type' => 'text', 'class' => 'personal-mail'];
	
			/* 
			# add spam protection questions
			$spamanswers = ['Albert', 'kalt', 'Gelb'];
			shuffle($spamanswers);
			$pluginDefinitions['public']['fields']['spamquestion'] = ['type' => 'checkboxlist', 'label' => 'Der Vorname von Einstein lautet', 'required' => true, 'options' => $spamanswers];
			*/
				
			# get all the fields and prefill them with the dafault-data, the user-data or old input data
			$fields = $fieldsModel->getFields($settings, 'plugins', $pluginName, $pluginDefinitions, 'public');

			# get Twig Instance
			$twig 	= $this->getTwig();

			# render each field and add it to the form
			$form = $twig->fetch('/partials/form.twig', ['fields' => $fields, 'itemName' => $pluginName, 'object' => 'plugins', 'buttonlabel' => $buttonlabel]);
		}
		
		return $form;
	}
}