<?php

use Typemill\Models\Helpers;
$timer['before session start']=microtime(true);

/************************
* START SESSION			*
************************/

session_start();

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
* 	LOAD TWIG VIEW		*
************************/

$container['view'] = function ($container) use ($settings){
	$path = array($settings['settings']['themePath'], $settings['settings']['authorPath']);
	
    $view = new \Slim\Views\Twig( $path, [
		'cache' => false,
		'autoescape' => false
    ]);
    
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

	return $view;
};

/************************
* 	LOAD FLASH MESSAGES	*
************************/

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

/****************************
* CREATE EVENT DISPATCHER	*
****************************/

$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

/************************
* LOAD PLUGINS 			*
************************/

$plugins 				= new Typemill\Plugins();
$pluginClassNames		= $plugins->load();
$routes = $middleware	= array();

foreach($pluginClassNames as $pluginClassName)
{
	$routes 			= $plugins->getNewRoutes($pluginClassName, $routes);
	$middleware[]		= $plugins->getNewMiddleware($pluginClassName);
	
	$dispatcher->addSubscriber(new $pluginClassName($container, $app));	
}

$dispatcher->dispatch('onPluginsInitialized');	

$container['dispatcher'] = function($container) use ($dispatcher)
{
	return $dispatcher;
};

/************************
* 	ADD MIDDLEWARE		*
************************/

$container['notFoundHandler'] = function($c)
{
	return new \Typemill\Handlers\NotFoundHandler($c['view']);
};

/************************
* 	ADD ROUTES			*
************************/

$timer['before router']=microtime(true);

require __DIR__ . '/Routes/api.php';
require __DIR__ . '/Routes/web.php';
