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

    protected function isXhr()
    {
    	if($this->container['request']->isXhr())
    	{
			return true;
		}
		return false;
    }

    protected function getParams()
    {
    	return $this->container['request']->getParams();
    }

    protected function returnJson($data)
    {
        return $this->container['response']
            ->withHeader("Content-Type", "application/json")
            ->withStatus(200)
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    protected function returnJsonError($data)
    {
        return $this->container['response']
            ->withHeader("Content-Type", "application/json")
            ->withStatus(400)
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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

	protected function activateAxios()
	{
		$this->container->assets->activateAxios();		
	}
	
	protected function activateVue()
	{
		$this->container->assets->activateVue();		
	}

	protected function activateTachyons()
	{
		$this->container->assets->activateTachyons();		
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
		elseif(isset($flash['publicform']) && $flash['publicform'][0] == 'bot')
		{
			return 'bot';
		}
		return false;
	}
	
	protected function generateForm($pluginName)
	{
		$fieldsModel = new Fields();
		
		$pluginDefinitions 	= \Typemill\Settings::getObjectSettings('plugins', $pluginName);
		$settings 			= $this->getSettings();
		$buttonlabel		= isset($settings['plugins'][$pluginName]['button_label']) ? $settings['plugins'][$pluginName]['button_label'] : false;
		$recaptcha			= isset($settings['plugins'][$pluginName]['recaptcha']) ? $settings['plugins'][$pluginName]['recaptcha_webkey'] : false;
		
		if(isset($pluginDefinitions['public']['fields']))
		{
			# add simple honeypot spam protection
			$pluginDefinitions['public']['fields']['personal-mail'] = ['type' => 'text', 'class' => 'personal-mail'];
			
			# get all the fields and prefill them with the dafault-data, the user-data or old input data
			$fields = $fieldsModel->getFields($settings, 'plugins', $pluginName, $pluginDefinitions, 'public');

			# get Twig Instance
			$twig 	= $this->getTwig();

			# render each field and add it to the form
			$form = $twig->fetch('/partials/form.twig', ['fields' => $fields, 'itemName' => $pluginName, 'object' => 'plugins', 'recaptcha_webkey' => $recaptcha, 'buttonlabel' => $buttonlabel]);
		}
		
		return $form;
	}
}