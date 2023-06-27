<?php

use Slim\Routing\RouteCollectorProxy;
use Typemill\Middleware\WebRedirectIfAuthenticated;
use Typemill\Middleware\WebRedirectIfUnauthenticated;
use Typemill\Middleware\WebAuthorization;
use Typemill\Controllers\ControllerWebAuth;
use Typemill\Controllers\ControllerWebSystem;
use Typemill\Controllers\ControllerWebAuthor;
use Typemill\Controllers\ControllerWebFrontend;
use Typemill\Controllers\ControllerWebDownload;

# login/register
$app->group('/tm', function (RouteCollectorProxy $group) {

	$group->get('/login', ControllerWebAuth::class . ':show')->setName('auth.show');
	$group->post('/login', ControllerWebAuth::class . ':login')->setName('auth.login');

})->add(new WebRedirectIfAuthenticated($routeParser, $settings));

# author and editor area, requires authentication
$app->group('/tm', function (RouteCollectorProxy $group) use ($routeParser,$acl) {

	# Admin Area
	$group->get('/logout', ControllerWebAuth::class . ':logout')->setName('auth.logout');
	$group->get('/system', ControllerWebSystem::class . ':showSettings')->setName('settings.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'show')); # admin;
	$group->get('/license', ControllerWebSystem::class . ':showLicense')->setName('license.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'show')); # admin;
	$group->get('/themes', ControllerWebSystem::class . ':showThemes')->setName('themes.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'show')); # admin;
	$group->get('/plugins', ControllerWebSystem::class . ':showPlugins')->setName('plugins.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'show')); # admin;
	$group->get('/account', ControllerWebSystem::class . ':showAccount')->setName('user.account')->add(new WebAuthorization($routeParser, $acl, 'account', 'view')); # member;
	$group->get('/users', ControllerWebSystem::class . ':showUsers')->setName('users.show')->add(new WebAuthorization($routeParser, $acl, 'user', 'show')); # admin;
	$group->get('/user/new', ControllerWebSystem::class . ':newUser')->setName('user.new')->add(new WebAuthorization($routeParser, $acl, 'user', 'create')); # admin;
	$group->get('/user/{username}', ControllerWebSystem::class . ':showUser')->setName('user.show')->add(new WebAuthorization($routeParser, $acl, 'user', 'show')); # admin;

	# Author Area
	$group->get('/content/visual[/{route:.*}]', ControllerWebAuthor::class . ':showBlox')->setName('content.visual')->add(new WebAuthorization($routeParser, $acl, 'mycontent', 'view'));
	$group->get('/content/raw[/{route:.*}]', ControllerWebAuthor::class . ':showRaw')->setName('content.raw')->add(new WebAuthorization($routeParser, $acl, 'mycontent', 'view'));

})->add(new WebRedirectIfUnauthenticated($routeParser));

$app->redirect('/tm', $routeParser->urlFor('auth.show'), 302);
$app->redirect('/tm/', $routeParser->urlFor('auth.show'), 302);

# same with setup redirect


# downloads
$app->get('/media/files[/{params:.*}]', ControllerWebDownload::class . ':download')->setName('download.file');

# website
$app->get('/[{route:.*}]', ControllerWebFrontend::class . ':index')->setName('home');