<?php

use Slim\Routing\RouteCollectorProxy;
use Typemill\Controllers\ControllerWebSetup;

#$app->redirect('/tm', $routeParser->urlFor('auth.show'), 302);

$app->get('/tm/setup', ControllerWebSetup::class . ':show')->setName('setup.show');
$app->post('/tm/setup', ControllerWebSetup::class . ':create')->setName('setup.create');
$app->redirect('/[{params:.*}]', $routeParser->urlFor('setup.show'), 302);