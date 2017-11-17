<?php

use Typemill\Controllers\PageController;
use Typemill\Controllers\SetupController;

if(!isset($userSettings))
{
	$app->get('/setup', SetupController::class . ':setup')->setName('setup');
	$app->post('/setup', SetupController::class . ':save')->setName('save');
}

foreach($routes as $pluginRoute)
{
	$method = $pluginRoute['httpMethod'];
	$route	= $pluginRoute['route'];
	$class	= $pluginRoute['class'];

	$app->{$method}($route, $class);		
}

$app->get('/[{params:.*}]', PageController::class . ':index');