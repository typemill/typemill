<?php

use Typemill\Controllers\PageController;
use Typemill\Controllers\SetupController;

if(!isset($userSettings))
{
	$app->get('/setup', SetupController::class . ':setup')->setName('setup');
	$app->post('/setup', SetupController::class . ':save')->setName('save');
}

$app->get('/[{params:.*}]', PageController::class . ':index');