<?php

use Typemill\Events\OnSettingsLoaded;
use Typemill\Events\OnPluginsLoaded;

/****************************
* CREATE EVENT DISPATCHER	*
****************************/

$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

/************************
* LOAD SETTINGS			*
************************/

$settings = Typemill\Settings::loadSettings();

/************************
* INITIATE SLIM 		*
************************/

$app = new \Slim\App($settings);

/************************
*  GET SLIM CONTAINER	*
************************/

$container = $app->getContainer();

/************************
* LOAD PLUGINS 			*
************************/

$plugins 					= new Typemill\Plugins();
$pluginNames				= $plugins->load();
$pluginSettings['plugins'] 	= array();

$routes = $middleware 		= array();

foreach($pluginNames as $pluginName)
{
	$className			= $pluginName['className'];
	$name				= $pluginName['name'];
	
	/* if plugin is not in user settings yet */
	if(!isset($settings['settings']['plugins'][$name]))
	{
		/* then read the plugin default settings and write them to the users setting.yaml */
		$updateSettingsYaml = Typemill\settings::addPluginSettings($name);
		
		/* if default settings are written successfully to user settings, update the pluginSettings */
		if($updateSettingsYaml)
		{
			$pluginSettings['plugins'][$name] = $updateSettingsYaml;
		}
		/* if not, then settingsYaml does not exist, so set plugin to false for further use */
		else
		{
			$pluginSettings['plugins'][$name] = false;			
		}
		/* get settings from di-container and update them with the new plugin Settings */
		$DIsettings = $container->get('settings');
		$DIsettings->replace($pluginSettings);		
	}

	/* if the plugin is activated, add routes/middleware and add plugin as event subscriber */
	if(isset($settings['settings']['plugins'][$name]['active']) && $settings['settings']['plugins'][$name]['active'] != false)
	{
		$routes 			= $plugins->getNewRoutes($className, $routes);
		$middleware			= $plugins->getNewMiddleware($className, $middleware);
		
		$dispatcher->addSubscriber(new $className($container));
	}
}

/* dispatch the event onPluginsLoaded */
$dispatcher->dispatch('onPluginsLoaded', new OnPluginsLoaded($pluginNames));

/* dispatch settings event and get all setting-updates from plugins */
/* TODO, how to update the settings with a plugin? You cannot replace the full settings in the container, so you have to add settings in the container directly */
$dispatcher->dispatch('onSettingsLoaded', new OnSettingsLoaded($settings))->getData();

/******************************
* ADD DISPATCHER TO CONTAINER *
******************************/

$container['dispatcher'] = function($container) use ($dispatcher)
{
	return $dispatcher;
};

/********************************
* ADD ASSET-FUNCTION FOR TWIG	*
********************************/

$container['assets'] = function($c)
{
	return new \Typemill\Assets($c['request']->getUri()->getBaseUrl());
};


/************************
* 	DECIDE FOR SESSION	*
************************/

$session_segments = array('setup', 'tm/', 'api/', '/setup', '/tm/', '/api/');
$path = $container['request']->getUri()->getPath();
$container['flash'] = false;
$container['csrf'] = false;

foreach($session_segments as $segment)
{	
	if(substr( $path, 0, strlen($segment) ) === $segment)
	{		
		// configure session
		ini_set( 'session.cookie_httponly', 1 );
		ini_set('session.use_strict_mode', 1);
		if($container['request']->getUri()->getScheme() == 'https')
		{
			ini_set('session.cookie_secure', 1);
			session_name('__Secure-typemill-session');
		}
		else
		{
			session_name('typemill-session');
		}
		
		// add csrf-protection
		$container['csrf'] = function ($c)
		{
			$guard = new \Slim\Csrf\Guard();
			$guard->setPersistentTokenMode(true);
			
			return $guard;
		};
		
		// add flash to container
		$container['flash'] = function () 
		{
			return new \Slim\Flash\Messages();
		};
		
		// start session
		session_start();
	}
}

/************************
* 	LOAD TWIG VIEW		*
************************/

$container['view'] = function ($container)
{
	$path = array($container->get('settings')['themePath'], $container->get('settings')['authorPath']);
	
    $view = new \Slim\Views\Twig( $path, [
		'cache' => false,
		'autoescape' => false,
		'debug' => true
    ]);
    
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
	$view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new Typemill\Extensions\TwigUserExtension());
	$view->addExtension(new Typemill\Extensions\TwigMarkdownExtension());	
	
	/* use {{ base_url() }} in twig templates */
	$view['base_url']	 = $container['request']->getUri()->getBaseUrl();
	$view['current_url'] = $container['request']->getUri()->getPath();
	
	/* if session route, add flash messages and csrf-protection */
	if($container['flash'])
	{
		$view->getEnvironment()->addGlobal('flash', $container->flash);
		$view->addExtension(new Typemill\Extensions\TwigCsrfExtension($container['csrf']));
	}

	/* add asset-function to all views */
	$view->getEnvironment()->addGlobal('assets', $container->assets);

	return $view;
};

$container->dispatcher->dispatch('onTwigLoaded');

/***************************
* 	ADD NOT FOUND HANDLER  *
***************************/

$container['notFoundHandler'] = function($c)
{
	return new \Typemill\Handlers\NotFoundHandler($c['view']);
};

/************************
* 	ADD MIDDLEWARE  	*
************************/

foreach($middleware as $pluginMiddleware)
{
	$middlewareClass 	= $pluginMiddleware['classname'];
	$middlewareParams	= $pluginMiddleware['params'];
	if(class_exists($middlewareClass))
	{
		$app->add(new $middlewareClass($middlewareParams));
	}
}

if($container['flash'])
{
	$app->add(new \Typemill\Middleware\ValidationErrorsMiddleware($container['view']));
	$app->add(new \Typemill\Middleware\OldInputMiddleware($container['view']));
	$app->add($container->get('csrf'));	
}

/************************
* 	ADD ROUTES			*
************************/

require __DIR__ . '/Routes/Api.php';
require __DIR__ . '/Routes/Web.php';