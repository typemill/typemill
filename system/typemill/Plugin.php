<?php

namespace Typemill;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Typemill\Models\Fields;
use Typemill\Models\WriteYaml;
use Typemill\Models\Validation;
use Typemill\Extensions\ParsedownExtension;

abstract class Plugin implements EventSubscriberInterface
{
	protected $container;

	protected $path;

	protected $adminpath = false;

    /**
     * Constructor
     *
     */
	
    public function __construct($container)
    {
		$this->container 	= $container;
/*
		$this->path 		= trim($this->container['request']->getUri()->getPath(),"/");

		if(substr($this->path, 0, 3) === "tm/")
		{
			$this->adminpath = true;
		}
*/
    }

    protected function isXhr()
    {
    	return true;
    	if($this->container['request']->isXhr())
    	{
			return true;
		}
		return false;
    }

    protected function getParams()
    {
    	return true;
    	return $this->container['request']->getParams();
    }

    protected function returnJson($data)
    {
    	return true;
        return $this->container['response']
            ->withHeader("Content-Type", "application/json")
            ->withStatus(200)
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    protected function returnJsonError($data)
    {
    	return true;
        return $this->container['response']
            ->withHeader("Content-Type", "application/json")
            ->withStatus(400)
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
	
	protected function getSettings()
	{
		return true;
		return $this->container->get('settings');
	}
	
	protected function getPluginSettings($plugin)
	{
		return true;
		return $this->container->get('settings')['plugins'][$plugin];
	}

	protected function getRoute()
	{
		return true;
		return $this->container['request']->getUri()->withUserInfo('');
	}
	
	protected function getPath()
	{
		return true;
		return $this->container['request']->getUri()->getPath();
	}
	
	protected function getDispatcher()
	{
		return true;
		return $this->container['dispatcher'];
	}
	
	protected function getTwig()
	{
		return true;
		return $this->container['view'];
	}
	
	protected function addTwigGlobal($name, $class)
	{
		return true;
		$this->container->view->getEnvironment()->addGlobal($name, $class);
	}
	
	protected function addTwigFilter($name, $filter)
	{
		return true;
		$filter = new \Twig_SimpleFilter($name, $filter);
		$this->container->view->getEnvironment()->addFilter($filter);
	}
	
	protected function addTwigFunction($name, $function)
	{
		return true;
		$function = new \Twig_SimpleFunction($name, $function);
		$this->container->view->getEnvironment()->addFunction($function);
	}

	protected function addJS($JS)
	{
		return true;
		$this->container->assets->addJS($JS);
	}

	protected function addEditorJS($JS)
	{
		return true;
		$this->container->assets->addEditorJS($JS);
	}

	protected function addInlineJS($JS)
	{
		return true;
		$this->container->assets->addInlineJS($JS);
	}

	protected function addSvgSymbol($symbol)
	{
		return true;
		$this->container->assets->addSvgSymbol($symbol);
	}

	protected function addEditorInlineJS($JS)
	{
		return true;
		$this->container->assets->addEditorInlineJS($JS);
	}
	
	protected function addCSS($CSS)
	{
		return true;
		$this->container->assets->addCSS($CSS);		
	}
	
	protected function addInlineCSS($CSS)
	{
		return true;
		$this->container->assets->addInlineCSS($CSS);		
	}

	protected function addEditorCSS($CSS)
	{
		return true;
		$this->container->assets->addEditorCSS($CSS);
	}

	protected function getMeta()
	{
		return true;
		return $this->container->assets->meta;
	}

	public function addMeta($key,$meta)
	{
		return true;
		$this->container->assets->addMeta($key, $meta);
	}

	protected function activateAxios()
	{
		return true;
		$this->container->assets->activateAxios();		
	}
	
	protected function activateVue()
	{
		return true;
		$this->container->assets->activateVue();		
	}

	protected function activateTachyons()
	{
		return true;
		$this->container->assets->activateTachyons();		
	}	

	protected function markdownToHtml($markdown)
	{
		return true;
		$parsedown 		= new ParsedownExtension();
		
		$contentArray 	= $parsedown->text($markdown);
		$html			= $parsedown->markup($contentArray);
		
		return $html;
	}
	
	protected function getFormData($pluginName)
	{
		return true;
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
	
	protected function generateForm($pluginName, $routename)
	{
		$fieldsModel = new Fields();
		
		$settings 			= $this->getSettings();
		$form 				= false;

		$pluginDefinitions 	= \Typemill\Settings::getObjectSettings('plugins', $pluginName);
		if(isset($settings['plugins'][$pluginName]['publicformdefinitions']) && $settings['plugins'][$pluginName]['publicformdefinitions'] != '')
		{
			$arrayFromYaml = \Symfony\Component\Yaml\Yaml::parse($settings['plugins'][$pluginName]['publicformdefinitions']);
			$pluginDefinitions['public']['fields'] = $arrayFromYaml;
		}

		$buttonlabel		= isset($settings['plugins'][$pluginName]['button_label']) ? $settings['plugins'][$pluginName]['button_label'] : false;
		$captchaoptions		= isset($settings['plugins'][$pluginName]['captchaoptions']) ? $settings['plugins'][$pluginName]['captchaoptions'] : false;
		$recaptcha			= isset($settings['plugins'][$pluginName]['recaptcha']) ? $settings['plugins'][$pluginName]['recaptcha_webkey'] : false;

		if($captchaoptions == 'disabled')
		{
			# in case a captcha has failed on another page like login, the captcha-session must be deleted, otherwise it will not pass the security middleware
			unset($_SESSION['captcha']);			
		}

		$fieldsModel = new Fields();

		if(isset($pluginDefinitions['public']['fields']))
		{			
			# get all the fields and prefill them with the dafault-data, the user-data or old input data
			$fields = $fieldsModel->getFields($settings, 'plugins', $pluginName, $pluginDefinitions, 'public');

			# get Twig Instance
			$twig 	= $this->getTwig();

			# render each field and add it to the form
			$form = $twig->fetch('/partials/form.twig', [	
				'routename'			=> $routename,
				'fields' 			=> $fields, 
				'itemName' 			=> $pluginName, 
				'object' 			=> 'plugins', 
				'buttonlabel' 		=> $buttonlabel,
				'captchaoptions'	=> $captchaoptions,
				'recaptcha_webkey' 	=> $recaptcha, 
			]);
		}
		
		return $form;
	}

	protected function validateParams($params)
	{
		$pluginName = key($params);

		if(isset($params[$pluginName]))
		{
			$userInput 			= $params[$pluginName];
			$settings 			= $this->getSettings();

			# get settings and start validation
			$originalSettings 	= \Typemill\Settings::getObjectSettings('plugins', $pluginName);
			if(isset($settings['plugins'][$pluginName]['publicformdefinitions']) && $settings['plugins'][$pluginName]['publicformdefinitions'] != '')
			{
				$arrayFromYaml 	= \Symfony\Component\Yaml\Yaml::parse($settings['plugins'][$pluginName]['publicformdefinitions']);
				$originalSettings['public']['fields'] = $arrayFromYaml;
			}
			elseif(isset($originalSettings['settings']['publicformdefinitions']))
			{
				$arrayFromYaml 	= \Symfony\Component\Yaml\Yaml::parse($originalSettings['settings']['publicformdefinitions']);
				$originalSettings['public']['fields'] = $arrayFromYaml;
			}

			$validate			= new Validation();

			if(isset($originalSettings['public']['fields']))
			{
				# flaten the multi-dimensional array with fieldsets to a one-dimensional array
				$originalFields = array();
				foreach($originalSettings['public']['fields'] as $fieldName => $fieldValue)
				{
					if(isset($fieldValue['fields']))
					{
						foreach($fieldValue['fields'] as $subFieldName => $subFieldValue)
						{
							$originalFields[$subFieldName] = $subFieldValue;
						}
					}
					else
					{
						$originalFields[$fieldName] = $fieldValue;
					}
				}

				# take the user input data and iterate over all fields and values
				foreach($userInput as $fieldName => $fieldValue)
				{
					# get the corresponding field definition from original plugin settings
					$fieldDefinition = isset($originalFields[$fieldName]) ? $originalFields[$fieldName] : false;

					if($fieldDefinition)
					{
						# validate user input for this field
						$validate->objectField($fieldName, $fieldValue, $pluginName, $fieldDefinition);
					}
					if(!$fieldDefinition && $fieldName != 'active')
					{
						$_SESSION['errors'][$pluginName][$fieldName] = array('This field is not defined!');
					}
				}

				if(isset($_SESSION['errors']))
				{
					$this->container->flash->addMessage('error', 'Please correct the errors');
					return false;
				}

				return $params[$pluginName];
			}
		}

		$this->container->flash->addMessage('error', 'The data from the form was invalid (missing or not defined)');
		return false;
	}
}