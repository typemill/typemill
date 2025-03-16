<?php

namespace Plugins\demo;

use Typemill\Plugin;
use Typemill\Models\Validation;
use Plugins\demo\demoController;
use Plugins\Demo\Text;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class demo extends Plugin
{
	# you can add a licence check here
	public static function setPremiumLicense()
	{
#		return false;
		return 'MAKER';
		# return 'BUSINESS';
	}

	# you can subscribe to the following events
    public static function getSubscribedEvents()
    {
		return [

			# all pages fired from system file
			'onSettingsLoaded' 			=> ['onSettingsLoaded', 0],
			'onPluginsLoaded' 			=> ['onPluginsLoaded', 0],
			'onSessionSegmentsLoaded' 	=> ['onSessionSegmentsLoaded', 0],
			'onRolesPermissionsLoaded' 	=> ['onRolesPermissionsLoaded', 0],
			'onResourcesLoaded' 		=> ['onResourcesLoaded', 0],

			# admin area fired from navigation model
			'onSystemnaviLoaded' 		=> ['onSystemnaviLoaded', 0],

			# all pages fired from controller
			'onTwigLoaded' 				=> ['onTwigLoaded', 0],

			# only content pages fired from parsedown extension and controllerApiShortcode????
			'onShortcodeFound' 			=> ['onShortcodeFound', 0],

			# frontend pages fired from ControllerWebFrontend
			'onPagetreeLoaded' 			=> ['onPagetreeLoaded', 0],
			'onBreadcrumbLoaded' 		=> ['onBreadcrumbLoaded', 0],
			'onItemLoaded' 				=> ['onItemLoaded', 0],
			'onMarkdownLoaded'			=> ['onMarkdownLoaded', 0],
			'onMetaLoaded'				=> ['onMetaLoaded', 0],
			'onRestrictionsLoaded' 		=> ['onRestrictionsLoaded', 0],
			'onContentArrayLoaded'		=> ['onContentArrayLoaded', 0],
			'onHtmlLoaded'				=> ['onHtmlLoaded', 0],
			'onPageReady'				=> ['onPageReady', 0] 
		];
    }

	# you can add new routes for public, api, or admin-area	
	public static function addNewRoutes()
	{
		return [ 

			# add a frontend route with a form
			[	
				'httpMethod' 	=> 'get', 
				'route' 		=> '/demo', 
				'name' 			=> 'demo.frontend', 
				'class' 		=> 'Plugins\demo\DemoController:index',
				# optionallly restrict page:
				# 'resource' 	=> 'account', 
				# 'privilege' 	=> 'view'
			],

			# add a frontend route to receive form data
			[	
				'httpMethod' 	=> 'post', 
				'route' 		=> '/demo', 
				'name' 			=> 'demo.send', 
				'class' 		=> 'Plugins\demo\DemoController:formdata',
				# optionallly restrict page:
				# 'resource' 	=> 'account', 
				# 'privilege' 	=> 'view'
			],

			# add an admin route
			[
				'httpMethod' 	=> 'get', 
				'route' 		=> '/tm/demo', 
				'name' 			=> 'demo.admin', 
				'class' 		=> 'Typemill\Controllers\ControllerWebSystem:blankSystemPage', 
				'resource' 		=> 'system', 
				'privilege' 	=> 'view'
			],

			# add an api route
			[
				'httpMethod' 	=> 'get', 
				'route' 		=> '/api/v1/demo', 
				'name' 			=> 'demo.api', 
				'class' 		=> 'Plugins\demo\demo:getDemoData', 
				'resource' 		=> 'system', 
				'privilege' 	=> 'view'
			],

			# add an api route
			[
				'httpMethod' 	=> 'post', 
				'route' 		=> '/api/v1/demo', 
				'name' 			=> 'demo.api', 
				'class' 		=> 'Plugins\demo\demo:storeDemoData', 
				'resource' 		=> 'system', 
				'privilege' 	=> 'view'
			],
		];
	}

	# you can add new middleware function, for example
	public static function addNewMiddleware()
	{
		
	}


	# settings are read only, you do not need to return anything
	public function onSettingsLoaded($settings)
	{
		$data = $settings->getData();

		# you also have access to settings from container through
		# $this->getSettings()

		# or access to the plugin settings (optionally with pluginname)
		# $this->getPluginSettings()

	}


	# use this if you have any dependencies with other plugins and want to check if they are active
	public function onPluginsLoaded($plugins)
	{
		$pluginnames = $plugins->getData();

		$plugins->setData($pluginnames);
	}


	# you can add a new session segment in frontend, for example if you add frontend fomrs
	public function onSessionSegmentsLoaded($segments)
	{
		$arrayOfSegments = $segments->getData();

		$segments->setData($arrayOfSegments);
	}


	# add new roles and permission
	public function onRolesPermissionsLoaded($rolespermissions)
	{
		$data = $rolespermissions->getData();

		$rolespermissions->setData($data);
	}


	# add new resources for roles and permissions
	public function onResourcesLoaded($resources)
	{
		$data = $resources->getData();

		$resources->setData($data);
	}


	# add new navi-items into the system area
	public function onSystemnaviLoaded($navidata)
	{
		$this->addSvgSymbol('<symbol id="icon-download" viewBox="0 0 24 24"><path d="M20 15v4c0 0.276-0.111 0.525-0.293 0.707s-0.431 0.293-0.707 0.293h-14c-0.276 0-0.525-0.111-0.707-0.293s-0.293-0.431-0.293-0.707v-4c0-0.552-0.448-1-1-1s-1 0.448-1 1v4c0 0.828 0.337 1.58 0.879 2.121s1.293 0.879 2.121 0.879h14c0.828 0 1.58-0.337 2.121-0.879s0.879-1.293 0.879-2.121v-4c0-0.552-0.448-1-1-1s-1 0.448-1 1zM13 12.586v-9.586c0-0.552-0.448-1-1-1s-1 0.448-1 1v9.586l-3.293-3.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414l5 5c0.092 0.092 0.202 0.166 0.324 0.217s0.253 0.076 0.383 0.076c0.256 0 0.512-0.098 0.707-0.293l5-5c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0z"></path></symbol>');

		$navi = $navidata->getData();

		$navi['Demo'] = ['title' => 'Demo','routename' => 'demo.admin', 'icon' => 'icon-download', 'aclresource' => 'system', 'aclprivilege' => 'view'];

		# if the use visits the system page of the plugin
		if(trim($this->route,"/") == 'tm/demo')
		{
			# set the navigation item active
			$navi['Demo']['active'] = true;

			# add the system application
			$this->addJS('/demo/js/systemdemo.js');
		}

		$navidata->setData($navi);
	}


	# the twig function is for everything you want to add and render in frontend
	public function onTwigLoaded()
	{
		if($this->editorroute)
		{
			$this->addJS('/demo/js/editordemo.js');
		}

		# get the twig-object
		# $twig   = $this->getTwig(); 
		
		# get the twig-template-loader
		# $loader = $twig->getLoader();	
		# $loader->addPath(__DIR__ . '/templates');

		# return $twig->render($this->container['response'], '/demo.twig', ['data' => 'data']);
	
		# you can add assets to all twig-views
		# $this->addInlineJS("console.info('my inline script;')");
		# $this->addJS('/demo/js/script.js');

		# you can add styles to all twig-views
		# $this->addInlineCSS('h1{color:red;}');
		# $this->addCSS('/demo/css/demo.css');
		
		# you can add your own global variables to all twig-views.
		# $this->addTwigGlobal('text', new Text());

		# you can add your own filter function to twig.
		# $this->addTwigFilter('rot13', function ($string) {
		# 	return str_rot13($string);
		# });

		# you can add your own function to a twig-views *
		# $this->addTwigFunction('myName', function(){
		# 	return 'My name is ';
		# });
	}


	# add a shortcode function to enhance the content area with new features
	public function onShortcodeFound($shortcode)
	{
	    # read the data of the shortcode
	    $shortcodeArray = $shortcode->getData();

	    # register your shortcode
		if(is_array($shortcodeArray) && $shortcodeArray['name'] == 'registershortcode')
		{
		    $shortcodeArray['data']['contactform'] = [];

		    $shortcode->setData($shortcodeArray);			
		}

	    # check if it is the shortcode name that we where looking for
	    if(is_array($shortcodeArray) && $shortcodeArray['name'] == 'contactform')
	    {
			# we found our shortcode, so stop firing the event to other plugins
			$shortcode->stopPropagation();

			# get the public forms for the plugin
			$contactform = $this->generateForm('demo.send');
			# add to a page
			# add as shortcode 
			# create new page

	 		# and return a html-snippet that replaces the shortcode on the page.
			$shortcode->setData($contactform);
	    }
	}

	# returns an array of item-objects, that represents the neavigation
	public function onPagetreeLoaded($pagetree)
	{
		$data = $pagetree->getData();

		$pagetree->setData($data);
	}

	# returns array of item objects that represent the breadcrumb
	public function onBreadcrumbLoaded($breadcrumb)
	{
		$data = $breadcrumb->getData();
		
		$breadcrumb->setData($data);
	}

	# returns the item of the current page
	public function onItemLoaded($item)
	{	
		$data = $item->getData();
		
		$item->setData($data);
	}
	
	# returns the markdown of the current page
	public function onMarkdownLoaded($markdown)
	{
		$data = $markdown->getData();
		
		$markdown->setData($data);
	}

	# returns the metadata (array) of the current page
	public function onMetaLoaded($meta)
	{
		$data = $meta->getData();
		
		$meta->setData($data);
	}

	# returns array with restriced role, defaultcontent and full markdown array
	public function onRestrictionsLoaded($restrictions)
	{
		$data = $restrictions->getData();

		$restrictions->setData($data);
	}	

	# returns the full content with ormats as an array
	public function onContentArrayLoaded($contentArray)
	{
		$data = $contentArray->getData();

		$contentArray->setData($data);
	}

	# returns the full content as html
	public function onHtmlLoaded($html)
	{
		$data = $html->getData();

		$html->setData($data);
	}	


	# add a new page into the system area
	public function onPageReady($data)
	{
		/*
		# admin stuff
		if($this->adminroute && $this->route == 'tm/demo')
		{
			$this->addJS('/ebookproducts/js/vue-ebookproducts.js');

			$pagedata = $data->getData();

			$twig 	= $this->getTwig();
			$loader = $twig->getLoader();
			$loader->addPath(__DIR__ . '/templates');
				
			# fetch the template and render it with twig
			$content = $twig->fetch('/ebookproducts.twig', []);

			$pagedata['content'] = $content;

			$data->setData($pagedata);
		}
		*/
	}


	#########################################
	#   	Add methods for new routes 		#
	#########################################

	# gets the centrally stored ebook-data for ebook-plugin in settings-area
	public function getDemoData(Request $request, Response $response, $args)
	{
		# gets file from /data/demo automatically, use getPluginData or getPluginYamlData
		$formdata = $this->getPluginYamlData('demotest.yaml');

		$response->getBody()->write(json_encode([
			'formdata'	=> $formdata
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}


	# gets the centrally stored ebook-data for ebook-plugin in settings-area
	public function storeDemoData(Request $request, Response $response, $args)
	{
		$params = $request->getParsedBody();

		# gets file from /data/demo automatically, use getPluginData or getPluginYamlData
		$result = $this->storePluginYamlData('demotest.yaml', $params['formdata']);

		if($result !== true)
		{
			$response->getBody()->write(json_encode([
				'errors'	=> $result,
				'message'	=> 'please correct the errors in the form.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		$response->getBody()->write(json_encode([
			'data'		=> $result,
			'message'	=> 'data stored successfully.'
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}
}