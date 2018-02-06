<?php

use Typemill\Events\LoadSettingsEvent;
use Typemill\Events\LoadPluginsEvent;

/************************
* START SESSION			*
************************/

session_start();

/****************************
* CREATE EVENT DISPATCHER	*
****************************/

$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

/************************
* LOAD SETTINGS			*
************************/

$settings = Typemill\settings::loadSettings();

/************************
* INITIATE SLIM 		*
************************/

$app = new \Slim\App($settings);

/************************
*  GET SLIM CONTAINER	*
************************/

$container = $app->getContainer();

/************************
* ADD CSRF PROTECTION 	*
************************/

$container['csrf'] = function ($c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setPersistentTokenMode(true);
	
	return $guard;
};

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
$dispatcher->dispatch('onPluginsLoaded', new LoadPluginsEvent($pluginNames));

/* dispatch settings event and get all setting-updates from plugins */
/* TODO, how to update the settings with a plugin? You cannot replace the full settings in the container, so you have to add settings in the container directly */
$dispatcher->dispatch('onSettingsLoaded', new LoadSettingsEvent($settings))->getData();

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

/******************************
* ADD FLASH MESSAGES FOR TIWG *
******************************/

$container['flash'] = function () 
{
    return new \Slim\Flash\Messages();
};

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
    $view->addExtension(new Typemill\Extensions\TwigCsrfExtension($container['csrf']));
	
	/* use {{ base_url() }} in twig templates */
	$view['base_url'] = $container['request']->getUri()->getBaseUrl();
	
	/* add flash messages to all views */
	$view->getEnvironment()->addGlobal('flash', $container->flash);

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

$app->add(new \Typemill\Middleware\ValidationErrorsMiddleware($container['view']));
$app->add(new \Typemill\Middleware\OldInputMiddleware($container['view']));
$app->add($container->get('csrf'));

/************************
* 	ADD ROUTES			*
************************/

require __DIR__ . '/Routes/api.php';
require __DIR__ . '/Routes/web.php';