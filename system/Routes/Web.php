<?php

use Typemill\Controllers\PageController;
use Typemill\Controllers\SetupController;
use Typemill\Controllers\AuthController;
use Typemill\Controllers\SettingsController;
use Typemill\Controllers\ContentController;
use Typemill\Middleware\RedirectIfUnauthenticated;
use Typemill\Middleware\RedirectIfAuthenticated;

if($settings['settings']['setup'])
{
	$app->get('/setup', SetupController::class . ':show')->setName('setup.show');
	$app->post('/setup', SetupController::class . ':create')->setName('setup.create');
}
else
{
	$app->get('/setup', AuthController::class . ':redirect');
}

$app->get('/tm-author', AuthController::class . ':redirect');
$app->get('/tm-author/login', AuthController::class . ':show')->setName('auth.show')->add(new RedirectIfAuthenticated($container['router']));
$app->post('/tm-author/login', AuthController::class . ':login')->setName('auth.login')->add(new RedirectIfAuthenticated($container['router']));
$app->get('/tm-author/logout', AuthController::class . ':logout')->setName('auth.logout')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/settings', SettingsController::class . ':showSettings')->setName('settings.show')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm-author/settings', SettingsController::class . ':saveSettings')->setName('settings.save')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/themes', SettingsController::class . ':showThemes')->setName('themes.show')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm-author/themes', SettingsController::class . ':saveThemes')->setName('themes.save')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/plugins', SettingsController::class . ':showPlugins')->setName('plugins.show')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm-author/plugins', SettingsController::class . ':savePlugins')->setName('plugins.save')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/user/new', SettingsController::class . ':newUser')->setName('user.new')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm-author/user/create', SettingsController::class . ':createUser')->setName('user.create')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm-author/user/update', SettingsController::class . ':updateUser')->setName('user.update')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->post('/tm-author/user/delete', SettingsController::class . ':deleteUser')->setName('user.delete')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/user/{username}', SettingsController::class . ':showUser')->setName('user.show')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/user', SettingsController::class . ':listUser')->setName('user.list')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));
$app->get('/tm-author/content', ContentController::class . ':showContent')->setName('content.show')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));

foreach($routes as $pluginRoute)
{
	$method = $pluginRoute['httpMethod'];
	$route	= $pluginRoute['route'];
	$class	= $pluginRoute['class'];

	$app->{$method}($route, $class);
}

$app->get('/[{params:.*}]', PageController::class . ':index')->setName('home');