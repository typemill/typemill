<?php

use Slim\Routing\RouteCollectorProxy;
use Typemill\Middleware\WebRedirectIfAuthenticated;
use Typemill\Middleware\WebRedirectIfUnauthenticated;
use Typemill\Middleware\WebAuthorization;
use Typemill\Controllers\ControllerWebAuth;
use Typemill\Controllers\ControllerWebSystem;
use Typemill\Controllers\ControllerWebAuthor;
use Typemill\Controllers\ControllerWebFrontend;
#use Slim\Views\TwigMiddleware;

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
	$group->get('/user/{username}', ControllerWebSystem::class . ':showUser')->setName('user.show')->add(new WebAuthorization($routeParser, $acl, 'user', 'show')); # admin;;

	# Author Area
	$group->get('/content/visual[/{route:.*}]', ControllerWebAuthor::class . ':showBlox')->setName('content.visual')->add(new WebAuthorization($routeParser, $acl, 'mycontent', 'view'));

})->add(new WebRedirectIfUnauthenticated($routeParser));

$app->redirect('/tm', $routeParser->urlFor('auth.show'), 302);
$app->redirect('/tm/', $routeParser->urlFor('auth.show'), 302);

# same with setup redirect

# website
$app->get('/[{params:.*}]', ControllerWebFrontend::class . ':index')->setName('home');



/*
use Typemill\Controllers\ControllerAuthorEditor;
use Typemill\Controllers\ControllerSettings;
use Typemill\Controllers\ControllerDownload;
use Typemill\Controllers\ControllerFrontendForms;
use Typemill\Controllers\ControllerFrontendAuth;
use Typemill\Controllers\ControllerFrontendSetup;
use Typemill\Middleware\RedirectIfNoAdmin;
use Typemill\Middleware\accessMiddleware;

if($settings['settings']['setup'])
{
	$app->get('/setup', ControllerFrontendSetup::class . ':show')->setName('setup.show');
	$app->post('/setup', ControllerFrontendSetup::class . ':create')->setName('setup.create');
}
else
{
	$app->get('/setup', ControllerFrontendAuth::class . ':redirect');	
}
if($settings['settings']['welcome'])
{
	$app->get('/setup/welcome', ControllerFrontendSetup::class . ':welcome')->setName('setup.welcome')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
}
else
{
	$app->get('/setup/welcome', ControllerFrontendAuth::class . ':redirect')->setName('setup.welcome');	
}

$app->post('/tm/formpost', ControllerFrontendForms::class . ':savePublicForm')->setName('form.save');

$app->get('/tm', ControllerFrontendAuth::class . ':redirect');
$app->get('/tm/logout', ControllerFrontendAuth::class . ':logout')->setName('auth.logout')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));

if(isset($settings['settings']['recoverpw']) && $settings['settings']['recoverpw'])
{
	$app->get('/tm/recoverpw', ControllerFrontendAuth::class . ':showrecoverpassword')->setName('auth.recoverpwshow')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
	$app->post('/tm/recoverpw', ControllerFrontendAuth::class . ':recoverpassword')->setName('auth.recoverpw')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
	$app->get('/tm/recoverpwnew', ControllerFrontendAuth::class . ':showrecoverpasswordnew')->setName('auth.recoverpwshownew')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
	$app->post('/tm/recoverpwnew', ControllerFrontendAuth::class . ':createrecoverpasswordnew')->setName('auth.recoverpwnew')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
}


/*

MIGRATED

$app->get('/tm/settings', ControllerSettings::class . ':showSettings')->setName('settings.show')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'view'));
$app->post('/tm/settings', ControllerSettings::class . ':saveSettings')->setName('settings.save')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'update'));
$app->get('/tm/themes', ControllerSettings::class . ':showThemes')->setName('themes.show')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'view'));
$app->post('/tm/themes', ControllerSettings::class . ':saveThemes')->setName('themes.save')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'update'));
$app->get('/tm/plugins', ControllerSettings::class . ':showPlugins')->setName('plugins.show')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'view'));
$app->post('/tm/plugins', ControllerSettings::class . ':savePlugins')->setName('plugins.save')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'update'));
$app->get('/tm/account', ControllerSettings::class . ':showAccount')->setName('user.account')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'view'));
$app->get('/tm/login', ControllerFrontendAuth::class . ':show')->setName('auth.show')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
$app->post('/tm/login', ControllerFrontendAuth::class . ':login')->setName('auth.login')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));



$app->get('/tm/content/raw[/{params:.*}]', ControllerAuthorEditor::class . ':showContent')->setName('content.raw')->add(new accessMiddleware($container['router'], $container['acl'], 'content', 'view'));
$app->get('/tm/content/visual[/{params:.*}]', ControllerAuthorEditor::class . ':showBlox')->setName('content.visual')->add(new accessMiddleware($container['router'], $container['acl'], 'content', 'view'));
$app->get('/tm/content[/{params:.*}]', ControllerAuthorEditor::class . ':showEmpty')->setName('content.empty')->add(new accessMiddleware($container['router'], $container['acl'], 'content', 'view'));

$app->get('/media/files[/{params:.*}]', ControllerDownload::class . ':download')->setName('download.file');

foreach($routes as $pluginRoute)
{
	$method 	= $pluginRoute['httpMethod'];
	$route		= $pluginRoute['route'];
	$class		= $pluginRoute['class'];
	$resource 	= isset($pluginRoute['resource']) ? $pluginRoute['resource'] : NULL;
	$privilege 	= isset($pluginRoute['privilege']) ? $pluginRoute['privilege'] : NULL;

	if(isset($pluginRoute['name']))
	{
		$app->{$method}($route, $class)->setName($pluginRoute['name'])->add(new accessMiddleware($container['router'], $container['acl'], $resource, $privilege));
	}
	else
	{
		$app->{$method}($route, $class)->add(new accessMiddleware($container['router'], $container['acl'], $resource, $privilege));
	}
}

if($settings['settings']['setup'])
{
	$app->get('/[{params:.*}]', ControllerFrontendSetup::class . ':redirect');	
}
elseif(isset($settings['settings']['access']) && $settings['settings']['access'] != '')
{
	$app->get('/[{params:.*}]', ControllerFrontendWebsite::class . ':index')->setName('home')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'view'));
}
else
{
	$app->get('/[{params:.*}]', ControllerFrontendWebsite::class . ':index')->setName('home');
}
*/