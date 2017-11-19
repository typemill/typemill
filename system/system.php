<?php

/************************
* START SESSION			*
************************/

session_start();

/************************
* LOAD SETTINGS			*
************************/

$settings = Typemill\Settings::loadSettings();

/************************
* INITIATE SLIM 		*
************************/

$app = new \Slim\App($settings);

/************************
* 	SLIM CONTAINER		*
************************/

$container = $app->getContainer();

/************************
* 		LOAD TWIG		*
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

/************************
* 	NOT FOUND HANDLER	*
************************/

$container['notFoundHandler'] = function($c)
{
	return new \Typemill\Handlers\NotFoundHandler($c['view']);
};

require __DIR__ . '/Routes/api.php';
require __DIR__ . '/Routes/web.php';

?>