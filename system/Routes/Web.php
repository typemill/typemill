<?php

use Typemill\Controllers\PageController;
use Typemill\Controllers\FormController;
use Typemill\Controllers\SetupController;
use Typemill\Controllers\AuthController;
use Typemill\Controllers\SettingsController;
use Typemill\Controllers\ContentBackendController;
use Typemill\Middleware\RedirectIfUnauthenticated;
use Typemill\Middleware\RedirectIfAuthenticated;
use Typemill\Middleware\RedirectIfNoAdmin;

if($settings['settings']['setup'])
{
	$app->get('/setup', SetupController::class . ':show')->setName('setup.show');
	$app->post('/setup', SetupController::class . ':create')->setName('setup.create');
}
else
{
	$app->get('/setup', AuthController::class . ':redirect');	
}
if($settings['settings']['welcome'])
{
	$app->get('/setup/welcome', SetupController::class . ':welcome')->setName('setup.welcome')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
}
else
{
	$app->get('/setup/welcome', AuthController::class . ':redirect')->setName('setup.welcome');	
}

$app->post('/tm/formpost', FormController::class . ':savePublicForm')->setName('form.save');

$app->get('/tm', AuthController::class . ':redirect');
$app->get('/tm/login', AuthController::class . ':show')->setName('auth.show')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
$app->post('/tm/login', AuthController::class . ':login')->setName('auth.login')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
$app->get('/tm/logout', AuthController::class . ':logout')->setName('auth.logout')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));

$app->get('/tm/settings', SettingsController::class . ':showSettings')->setName('settings.show')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->post('/tm/settings', SettingsController::class . ':saveSettings')->setName('settings.save')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->get('/tm/themes', SettingsController::class . ':showThemes')->setName('themes.show')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->post('/tm/themes', SettingsController::class . ':saveThemes')->setName('themes.save')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->get('/tm/plugins', SettingsController::class . ':showPlugins')->setName('plugins.show')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->post('/tm/plugins', SettingsController::class . ':savePlugins')->setName('plugins.save')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->get('/tm/user/new', SettingsController::class . ':newUser')->setName('user.new')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));
$app->post('/tm/user/create', SettingsController::class . ':createUser')->setName('user.create')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));

$app->post('/tm/user/update', SettingsController::class . ':updateUser')->setName('user.update')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm/user/delete', SettingsController::class . ':deleteUser')->setName('user.delete')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm/user/{username}', SettingsController::class . ':showUser')->setName('user.show')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm/user', SettingsController::class . ':listUser')->setName('user.list')->add(new RedirectIfNoAdmin($container['router'], $container['flash']));

$app->get('/tm/content/raw[/{params:.*}]', ContentBackendController::class . ':showContent')->setName('content.raw')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm/content/visual[/{params:.*}]', ContentBackendController::class . ':showBlox')->setName('content.visual')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm/content[/{params:.*}]', ContentBackendController::class . ':showEmpty')->setName('content.empty')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));

foreach($routes as $pluginRoute)
{
	$method = $pluginRoute['httpMethod'];
	$route	= $pluginRoute['route'];
	$class	= $pluginRoute['class'];

	if(isset($pluginRoute['name']))
	{
		$app->{$method}($route, $class)->setName($pluginRoute['name']);
	}
	else
	{
		$app->{$method}($route, $class);
	}
}

$app->get('/[{params:.*}]', PageController::class . ':index')->setName('home');