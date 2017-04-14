<?php

/************************
* START SESSION			*
************************/

session_start();

/************************
* LOAD SETTINGS			*
************************/

$settings 		= require_once( __DIR__ . '/settings.php');

if(file_exists($settings['settings']['settingsPath'] . DIRECTORY_SEPARATOR . 'settings.yaml'))
{
	$yaml 			= new \Symfony\Component\Yaml\Parser();

	try {
		$userSettings 	= $yaml->parse( file_get_contents($settings['settings']['settingsPath'] . DIRECTORY_SEPARATOR . 'settings.yaml' ) );
	} catch (ParseException $e) {
		printf("Unable to parse the YAML string: %s", $e->getMessage());
	}
	
	$settings 		= array('settings' => array_merge($settings['settings'], $userSettings));
}

/************************
* INITIATE SLIM 		*
************************/

$app 			= new \Slim\App($settings);

/************************
* 	SLIM CONTAINER		*
************************/

$container 		= $app->getContainer();

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
	return new \System\Handlers\NotFoundHandler($c['view']);
};

require __DIR__ . '/Routes/web.php';
require __DIR__ . '/Routes/api.php';

?>