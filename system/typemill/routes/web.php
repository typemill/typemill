<?php

use Slim\Routing\RouteCollectorProxy;
use Typemill\Middleware\WebRedirectIfAuthenticated;
use Typemill\Middleware\WebRedirectIfUnauthenticated;
use Typemill\Middleware\WebAuthorization;
use Typemill\Middleware\CspHeadersMiddleware;
use Typemill\Controllers\ControllerWebSetup;
use Typemill\Controllers\ControllerWebAuth;
use Typemill\Controllers\ControllerWebRecover;
use Typemill\Controllers\ControllerWebSystem;
use Typemill\Controllers\ControllerWebAuthor;
use Typemill\Controllers\ControllerWebFrontend;
use Typemill\Controllers\ControllerWebDownload;

# LOGIN / REGISTER / RECOVER
$app->group('/tm', function (RouteCollectorProxy $group) use ($settings) {

	$group->get('/login', ControllerWebAuth::class . ':show')->setName('auth.show');
	$group->post('/login', ControllerWebAuth::class . ':login')->setName('auth.login');

	if(isset($settings['loginlink']) && $settings['loginlink'])
	{
		$group->get('/loginlink', ControllerWebAuth::class . ':loginlink')->setName('auth.link');
	}

	if(isset($settings['authcode']) && $settings['authcode'])
	{
		$group->post('/authcode', ControllerWebAuth::class . ':loginWithAuthcode')->setName('auth.authcode');
	}

	if(isset($settings['recoverpw']) && $settings['recoverpw'])
	{
		$group->get('/recover', ControllerWebRecover::class . ':showRecoverForm')->setName('auth.recoverform');
		$group->post('/recover', ControllerWebRecover::class . ':recoverPassword')->setName('auth.recover');
		$group->get('/reset', ControllerWebRecover::class . ':showPasswordResetForm')->setName('auth.resetform');
		$group->post('/reset', ControllerWebRecover::class . ':resetPassword')->setName('auth.reset');
	}

})->add(new CspHeadersMiddleware($settings, $cspFromPlugins, $cspFromTheme))->add(new WebRedirectIfAuthenticated($routeParser, $settings));

# AUTHOR AREA (requires authentication)
$app->group('/tm', function (RouteCollectorProxy $group) use ($routeParser,$acl) {

	# Admin Area
	$group->get('/logout', ControllerWebAuth::class . ':logout')->setName('auth.logout');
	$group->get('/system', ControllerWebSystem::class . ':showSettings')->setName('settings.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'read')); # manager;
	$group->get('/license', ControllerWebSystem::class . ':showLicense')->setName('license.show')->add(new WebAuthorization($routeParser, $acl, 'user', 'read')); # admin;
	$group->get('/themes', ControllerWebSystem::class . ':showThemes')->setName('themes.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'read')); # manager;
	$group->get('/plugins', ControllerWebSystem::class . ':showPlugins')->setName('plugins.show')->add(new WebAuthorization($routeParser, $acl, 'system', 'read')); # manager;
	$group->get('/account', ControllerWebSystem::class . ':showAccount')->setName('user.account')->add(new WebAuthorization($routeParser, $acl, 'account', 'read')); # member;
	$group->get('/users', ControllerWebSystem::class . ':showUsers')->setName('users.show')->add(new WebAuthorization($routeParser, $acl, 'user', 'read')); # admin;
	$group->get('/user/new', ControllerWebSystem::class . ':newUser')->setName('user.new')->add(new WebAuthorization($routeParser, $acl, 'user', 'create')); # admin;
	$group->get('/user/{username}', ControllerWebSystem::class . ':showUser')->setName('user.show')->add(new WebAuthorization($routeParser, $acl, 'user', 'read')); # admin;

	# Author Area
	$group->get('/content/visual[/{route:.*}]', ControllerWebAuthor::class . ':showBlox')->setName('content.visual')->add(new WebAuthorization($routeParser, $acl, 'mycontent', 'read'));
	$group->get('/content/raw[/{route:.*}]', ControllerWebAuthor::class . ':showRaw')->setName('content.raw')->add(new WebAuthorization($routeParser, $acl, 'mycontent', 'read'));

})->add(new CspHeadersMiddleware($settings, $cspFromPlugins, $cspFromTheme))->add(new WebRedirectIfUnauthenticated($routeParser));

$app->redirect('/tm', $routeParser->urlFor('auth.show'), 302);
$app->redirect('/tm/', $routeParser->urlFor('auth.show'), 302);
$app->redirect('/tm/authcode', $routeParser->urlFor('auth.show'), 302);

# downloads
$app->get('/media/files[/{params:.*}]', ControllerWebDownload::class . ':download')->setName('download.file');

# web-routes from plugins
if(isset($routes['web']) && !empty($routes['web']))
{
	foreach($routes['web'] as $pluginRoute)
	{			
		$method 	= $pluginRoute['httpMethod'] ?? false;
		$route		= $pluginRoute['route'] ?? false;
		$class		= $pluginRoute['class'] ?? false;
		$name 		= $pluginRoute['name'] ?? false;
		$resource 	= $pluginRoute['resource'] ?? false;
		$privilege 	= $pluginRoute['privilege'] ?? false;

		if($resources && $privilege)
		{
			$app->{$method}($route, $class)->setName($name)->add(new CspHeadersMiddleware($settings, $cspFromPlugins, $cspFromTheme))->add(new WebAuthorization($routeParser, $acl, $resource, $privilege))->add(new WebRedirectIfUnauthenticated($routeParser));
		}
		else
		{
			$app->{$method}($route, $class)->setName($name)->add(new CspHeadersMiddleware($settings, $cspFromPlugins, $cspFromTheme));
		}
	}
}

if(isset($settings['access']) && $settings['access'] != '')
{
	# if access for website is restricted
	$app->get('/[{route:.*}]', ControllerWebFrontend::class . ':index')->setName('home')->add(new CspHeadersMiddleware($settings, $cspFromPlugins, $cspFromTheme))->add(new WebRedirectIfUnauthenticated($routeParser));
}
else
{
	# if access is not restricted
	$app->get('/[{route:.*}]', ControllerWebFrontend::class . ':index')->setName('home')->add(new CspHeadersMiddleware($settings, $cspFromPlugins, $cspFromTheme));
}
