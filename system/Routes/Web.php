<?php
use Typemill\Controllers\ControllerAuthorEditor;
use Typemill\Controllers\ControllerSettings;
use Typemill\Controllers\ControllerFrontendWebsite;
use Typemill\Controllers\ControllerFrontendForms;
use Typemill\Controllers\ControllerFrontendAuth;
use Typemill\Controllers\ControllerFrontendSetup;
use Typemill\Middleware\RedirectIfUnauthenticated;
use Typemill\Middleware\RedirectIfAuthenticated;
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
$app->get('/tm/login', ControllerFrontendAuth::class . ':show')->setName('auth.show')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
$app->post('/tm/login', ControllerFrontendAuth::class . ':login')->setName('auth.login')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
$app->get('/tm/logout', ControllerFrontendAuth::class . ':logout')->setName('auth.logout')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));

if(isset($settings['settings']['recoverpw']) && $settings['settings']['recoverpw'])
{
	$app->get('/tm/recoverpw', ControllerFrontendAuth::class . ':showrecoverpassword')->setName('auth.recoverpwshow')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
	$app->post('/tm/recoverpw', ControllerFrontendAuth::class . ':recoverpassword')->setName('auth.recoverpw')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
	$app->get('/tm/recoverpwnew', ControllerFrontendAuth::class . ':showrecoverpasswordnew')->setName('auth.recoverpwshownew')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
	$app->post('/tm/recoverpwnew', ControllerFrontendAuth::class . ':createrecoverpasswordnew')->setName('auth.recoverpwnew')->add(new RedirectIfAuthenticated($container['router'], $container['settings']));
}

$app->get('/tm/settings', ControllerSettings::class . ':showSettings')->setName('settings.show')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'view'));
$app->post('/tm/settings', ControllerSettings::class . ':saveSettings')->setName('settings.save')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'update'));

$app->get('/tm/themes', ControllerSettings::class . ':showThemes')->setName('themes.show')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'view'));
$app->post('/tm/themes', ControllerSettings::class . ':saveThemes')->setName('themes.save')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'update'));

$app->get('/tm/plugins', ControllerSettings::class . ':showPlugins')->setName('plugins.show')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'view'));
$app->post('/tm/plugins', ControllerSettings::class . ':savePlugins')->setName('plugins.save')->add(new accessMiddleware($container['router'], $container['acl'], 'system', 'update'));

$app->get('/tm/account', ControllerSettings::class . ':showAccount')->setName('user.account')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'view'));
$app->get('/tm/user/new', ControllerSettings::class . ':newUser')->setName('user.new')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'create'));
$app->post('/tm/user/create', ControllerSettings::class . ':createUser')->setName('user.create')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'create'));
$app->post('/tm/user/update', ControllerSettings::class . ':updateUser')->setName('user.update')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'update'));
$app->post('/tm/user/delete', ControllerSettings::class . ':deleteUser')->setName('user.delete')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'delete'));
$app->get('/tm/user/{username}', ControllerSettings::class . ':showUser')->setName('user.show')->add(new accessMiddleware($container['router'], $container['acl'], 'user', 'view'));
$app->get('/tm/users', ControllerSettings::class . ':listUser')->setName('user.list')->add(new accessMiddleware($container['router'], $container['acl'], 'userlist', 'view'));

$app->get('/tm/content/raw[/{params:.*}]', ControllerAuthorEditor::class . ':showContent')->setName('content.raw')->add(new accessMiddleware($container['router'], $container['acl'], 'content', 'view'));
$app->get('/tm/content/visual[/{params:.*}]', ControllerAuthorEditor::class . ':showBlox')->setName('content.visual')->add(new accessMiddleware($container['router'], $container['acl'], 'content', 'view'));
$app->get('/tm/content[/{params:.*}]', ControllerAuthorEditor::class . ':showEmpty')->setName('content.empty')->add(new accessMiddleware($container['router'], $container['acl'], 'content', 'view'));

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