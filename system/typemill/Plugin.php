<?php

namespace Typemill;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use DI\Container;
use Typemill\Models\StorageWrapper;
use Typemill\Models\Extension;
use Typemill\Models\Validation;
use Typemill\Models\Fields;
use Typemill\Extensions\ParsedownExtension;


abstract class Plugin implements EventSubscriberInterface
{
	protected $container;

	protected $route;

	protected $urlinfo;

	protected $adminroute = false;

	protected $editorroute = false;

	public function __construct(Container $container)
	{

		$this->container 	= $container;
		$this->urlinfo 		= $this->container->get('urlinfo');
		$this->route  		= $this->urlinfo['route'];

		if($this->route != '/')
		{
			$this->route 		= ltrim($this->route, '/');
		}

		if(str_starts_with($this->route, 'tm/'))
		{
			$this->adminroute = true;
		}

		if(str_starts_with($this->route, 'tm/content/'))
		{
			$this->editorroute = true;
		}
	}
	
	protected function getSettings()
	{
		return $this->container->get('settings');
	}
	
	protected function getPluginSettings($pluginname = false)
	{
#		$pluginClass = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['class'];

		$pluginname = $this->getPluginName($pluginname);

		if($pluginname && isset($this->container->get('settings')['plugins'][$pluginname]))
		{
			return $this->container->get('settings')['plugins'][$pluginname];
		}

		return false;
	}

	protected function getPluginData($filename, $pluginname = false)
	{
		$pluginname 	= $this->getPluginName($pluginname);

		$storageClass 	= $this->container->get('settings')['storage'];
		$storage 		= new StorageWrapper($storageClass);
		
		$data 			= $storage->getFile('dataFolder', $pluginname, $filename);

		return $data;
	}

	protected function getPluginYamlData($filename, $pluginname = false)
	{
		$pluginname 	= $this->getPluginName($pluginname);

		$storageClass 	= $this->container->get('settings')['storage'];
		$storage 		= new StorageWrapper($storageClass);
		
		$data 			= $storage->getYaml('dataFolder', $pluginname, $filename);

		return $data;
	}

	protected function storePluginData($filename, $data, $method = NULL, $pluginname = false)
	{
		$pluginname 	= $this->getPluginName($pluginname);

		$storageClass 	= $this->container->get('settings')['storage'];
		$storage 		= new StorageWrapper($storageClass);
		
		$result 		= $storage->writeFile('dataFolder', $pluginname, $filename, $data, $method = NULL);

		if($result)
		{
			return true;
		}

		return $storage->getError();
	}

	protected function storePluginYamlData(string $filename, array $data, $pluginname = false)
	{
		$pluginname 	= $this->getPluginName($pluginname);

		# validation
		$extension 			= new Extension();
		$pluginDefinitions 	= $extension->getPluginDefinition($pluginname);
		$formDefinitions 	= $pluginDefinitions['system']['fields'] ?? false;

		if($formDefinitions)
		{
# where can we add this method so we can use it everywhere?
#			$formdefinitions 	= $this->addDatasets($formdefinitions);
	
			$validate = new Validation();

			$validatedOutput = $validate->recursiveValidation($formDefinitions, $data);
			if(!empty($validate->errors))
			{
				return $validate->errors;
			}
		}

		$storageClass 	= $this->container->get('settings')['storage'];
		$storage 		= new StorageWrapper($storageClass);
		
		$result 		= $storage->updateYaml('dataFolder', $pluginname, $filename, $data);

		if($result)
		{
			return true;
		}

		return $storage->getError();
	}

	protected function deletePluginData($filename, $pluginname = false)
	{
		$pluginname 	= $this->getPluginName($pluginname);

		$storageClass 	= $this->container->get('settings')['storage'];
		$storage 		= new StorageWrapper($storageClass);
		
		$data 			= $storage->deleteFile('dataFolder', $pluginname, $filename);

		return $data;
	}

	private function getPluginName($pluginname = NULL)
	{
		if(!$pluginname)
		{
			$classname = get_called_class();
			
			if ($pos = strrpos($classname, '\\'))
			{
				$pluginname = strtolower(substr($classname, $pos + 1));
			}
		}

		return $pluginname;
	}

	protected function urlinfo()
	{
		return $this->container->get('urlinfo');
	}
	
	protected function getDispatcher()
	{
		return $this->container->get('dispatcher');
	}
	
	protected function getTwig()
	{
		return $this->container->get('view');
	}
	
	protected function addTwigGlobal($name, $class)
	{
		$this->container->get('view')->getEnvironment()->addGlobal($name, $class);
	}
	
	protected function addTwigFilter($name, $filter)
	{
		$filter = new \Twig_SimpleFilter($name, $filter);
		$this->container->get('view')->getEnvironment()->addFilter($filter);
	}
	
	protected function addTwigFunction($name, $function)
	{
		$function = new \Twig_SimpleFunction($name, $function);
		$this->container->get('view')->getEnvironment()->addFunction($function);
	}

	protected function addJS($JS)
	{
		$this->container->get('assets')->addJS($JS);
	}

	protected function addInlineJS($JS)
	{
		$this->container->get('assets')->addInlineJS($JS);
	}

	protected function addBloxConfigJS($JS)
	{
		$this->container->get('assets')->addBloxConfigJS($JS);
	}

	protected function addBloxConfigInlineJS($JS)
	{
		$this->container->get('assets')->addBloxConfigInlineJS($JS);
	}

	protected function addSvgSymbol($symbol)
	{
		$this->container->get('assets')->addSvgSymbol($symbol);
	}
	
	protected function addCSS($CSS)
	{
		$this->container->get('assets')->addCSS($CSS);
	}
	
	protected function addInlineCSS($CSS)
	{
		$this->container->get('assets')->addInlineCSS($CSS);		
	}

	protected function getMeta()
	{
		return $this->container->get('assets')->meta;
	}

	public function addMeta($key,$meta)
	{
		$this->container->get('assets')->addMeta($key, $meta);
	}

	protected function activateAxios()
	{
		$this->container->get('assets')->activateAxios();
	}
	
	protected function activateVue()
	{
		$this->container->get('assets')->activateVue();
	}

	protected function markdownToHtml($markdown)
	{
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
	
	protected function generateForm($routename, $pluginname = NULL)
	{
		$form 				= false;

		$fieldsModel 		= new Fields();
		$extensionModel 	= new Extension();
		
		$pluginSettings 	= $this->getPluginSettings();
		$pluginName 		= $this->getPluginName($pluginname);
		$pluginDefinitions 	= $extensionModel->getPluginDefinition($pluginName);

		# add field-definitions entered into author interface
		if(isset($pluginSettings['publicformdefinitions']) && $pluginSettings['publicformdefinitions'] != '')
		{
			$arrayFromYaml = \Symfony\Component\Yaml\Yaml::parse($pluginSettings['publicformdefinitions']);
			$pluginDefinitions['public']['fields'] = $arrayFromYaml;
		}

		$buttonlabel		= isset($pluginSettings['button_label']) ? $pluginSettings['button_label'] : false;
		$captchaoptions		= isset($pluginSettings['captchaoptions']) ? $pluginSettings['captchaoptions'] : false;
		$recaptcha			= isset($pluginSettings['recaptcha']) ? $pluginSettings['recaptcha_webkey'] : false;

		if($captchaoptions == 'disabled')
		{
			# in case a captcha has failed on another page like login, the captcha-session must be deleted, otherwise it will not pass the security middleware
			unset($_SESSION['captcha']);			
		}

		$fieldsModel = new Fields();

		if(isset($pluginDefinitions['public']['fields']))
		{
			$settings = $this->container->get('settings');

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
		$pluginname 		= $this->getPluginName();
		$userinput 			= $params[$pluginname] ?? false;

		if(!$userinput)
		{
			return false;
		}

		$pluginsettings 	= $this->getPluginSettings($pluginname);
		$extension 			= new Extension();
		$formdefinitions 	= $extension->getPluginDefinition($pluginname);

		# if there are public form definitions, add them to the formdefinitions
		if(isset($pluginsettings['publicformdefinitions']) && $pluginsettings['publicformdefinitions'] != '')
		{
			$arrayFromYaml 	= \Symfony\Component\Yaml\Yaml::parse($pluginsettings['publicformdefinitions']);
			$formdefinitions['public']['fields'] = $arrayFromYaml;
		}
		elseif(isset($formdefinitions['settings']['publicformdefinitions']))
		{
			$arrayFromYaml 	= \Symfony\Component\Yaml\Yaml::parse($formdefinitions['settings']['publicformdefinitions']);
			$formdefinitions['public']['fields'] = $arrayFromYaml;
		}

		$validate			= new Validation();
		$validatedOutput 	= $validate->recursiveValidation($formdefinitions['public']['fields'], $userinput);
		
		if(!empty($validate->errors))
		{
			$_SESSION['errors'] = $validate->errors;
			
			return false;
		}

		return $validatedOutput;
	}
}