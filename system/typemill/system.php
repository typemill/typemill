<?php

use DI\Container;
use Slim\Middleware\ErrorMiddleware;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Psr7\Factory\UriFactory;
use Twig\Extension\DebugExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use RKA\Middleware\ProxyDetection;
use Typemill\Assets;
use Typemill\Models\Settings;
use Typemill\Models\License;
use Typemill\Static\Plugins;
use Typemill\Static\Translations;
use Typemill\Static\Permissions;
use Typemill\Static\Helpers;
use Typemill\Static\Urlinfo;
use Typemill\Events\OnSettingsLoaded;
use Typemill\Events\OnPluginsLoaded;
use Typemill\Events\OnSessionSegmentsLoaded;
use Typemill\Events\OnRolesPermissionsLoaded;
use Typemill\Events\OnResourcesLoaded;
use Typemill\Events\OnCspLoaded;
use Typemill\Events\OnTwigGlobalsLoaded;
use Typemill\Middleware\RemoveCredentialsMiddleware;
use Typemill\Middleware\SessionMiddleware;
use Typemill\Middleware\OldInputMiddleware;
use Typemill\Middleware\ValidationErrorsMiddleware;
use Typemill\Middleware\JsonBodyParser;
use Typemill\Middleware\FlashMessages;
use Typemill\Middleware\AssetMiddleware;
use Typemill\Middleware\SecurityMiddleware;
use Typemill\Middleware\CustomHeadersMiddleware;
use Typemill\Extensions\MediaExtension;
use Typemill\Extensions\TwigCsrfExtension;
use Typemill\Extensions\TwigUrlExtension;
use Typemill\Extensions\TwigUserExtension;
use Typemill\Extensions\TwigLanguageExtension;
use Typemill\Extensions\TwigMarkdownExtension;
use Typemill\Extensions\TwigMetaExtension;
use Typemill\Extensions\TwigPagelistExtension;
use Typemill\Extensions\TwigCaptchaExtension;

$timer = [];
$timer['start'] = microtime(true);

/****************************
* HIDE ERRORS BY DEFAULT    *
****************************/

$display_errors = 0;

ini_set('display_errors', $display_errors);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/****************************
* LOAD SETTINGS				*
****************************/

$settingsModel = new Settings();

$settings = $settingsModel->loadSettings();


/****************************
* HANDLE DISPLAY ERRORS 	  *
****************************/
if(isset($settings['displayErrorDetails']) && $settings['displayErrorDetails'])
{
	$display_errors = 1;
	ini_set('display_errors', $display_errors);
}

/****************************
* CREATE CONTAINER + APP   	*
****************************/

# https://www.slimframework.com/docs/v4/start/upgrade.html#changes-to-container
$container 				= new Container();
AppFactory::setContainer($container);

$app 					= AppFactory::create();
$container 				= $app->getContainer();

$responseFactory 		= $app->getResponseFactory();
$routeParser 			= $app->getRouteCollector()->getRouteParser();

# add route parser to container to use named routes in controller
$container->set('routeParser', $routeParser);


/*******************************
 *      Basepath               *
 ******************************/

# in slim 4 you alsways have to set application basepath
$basepath = preg_replace('/(.*)\/.*/', '$1', $_SERVER['SCRIPT_NAME']);
$app->setBasePath($basepath);


/****************
*    URLINFO 	*
****************/

# WE DO NOT NEED IT HERE?
# WE CAN ADD IT TO CONTAINER IN MIDDLEWARE AFTER PROXY DETECTION

# WE NEED FOR
# - LICENSE (base url)
# - Each plugin to add container
# - SESSION SEGMEŃTS (route)
# - TRANSLATIONS (route)
# - ASSETS (route)
# - TWIG URL EXTENSION
# - SESSION MIDDLEWARE

$uriFactory 			= new UriFactory();
$uri 					= $uriFactory->createFromGlobals($_SERVER);

if(
	isset($settings['proxy']) && 
	$settings['proxy'] && 
	isset($_SERVER['HTTP_X_FORWARDED_HOST']) &&
	isset($settings['fqdn']) &&
	$settings['fqdn'] != ''
)
{
	$fqdn = $uriFactory->createUri($settings['fqdn']);
	$uri = $uri->withScheme($fqdn->getScheme())
           		->withHost($fqdn->getHost())
           		->withPort($fqdn->getPort());	
}

$urlinfo 				= Urlinfo::getUrlInfo($basepath, $uri, $settings);

$timer['settings'] = microtime(true);

# set urlinfo
$container->set('urlinfo', $urlinfo);

$timer['container'] = microtime(true);

/****************************
* CREATE EVENT DISPATCHER		*
****************************/

$dispatcher = new EventDispatcher();

/****************************
*    	Check Licence		*
****************************/

$license = new License();

# checks if license is valid and returns scope
$settings['license'] = $license->getLicenseScope($urlinfo);

/****************************
* LOAD & UPDATE PLUGINS			*
****************************/
$plugins 				= Plugins::loadPlugins();
$routes 				= [];
$middleware				= [];
$pluginSettings 		= [];

# if there are less plugins in the scan than in the settings, then a plugin has been removed
if(isset($settings['plugins']) && (count($plugins) < count($settings['plugins'])) )
{
	$updateSettings = true;
}

foreach($plugins as $plugin)
{
	$pluginName			= $plugin['name'];
	$className			= $plugin['className'];

	# store existing plugin-settings to update settings later
	if(isset($settings['plugins'][$pluginName]))
	{
		$pluginSettings[$pluginName] = $settings['plugins'][$pluginName];
	}
	else
	{
		# it is a new plugin. Add it and set active to false
		$pluginSettings[$pluginName] = ['active' => false];
		
		# and set flag to refresh the settings
		$updateSettings = true;
	}

	# licence check
	$PluginLicense = Plugins::getPremiumLicense($className);
	if($PluginLicense)
	{
		if(!$settings['license'] OR !isset($settings['license'][$PluginLicense]))
		{
#			\Typemill\Static\Helpers\addLogEntry('No License: ' . $pluginName);
			if($pluginSettings[$pluginName]['active'])
			{
				$pluginSettings[$pluginName]['active'] = false;
				$updateSettings = true;
			}
		}
	}

	# if the plugin is activated, add routes/middleware and add plugin as event subscriber
	if(isset($settings['plugins'][$pluginName]['active']) && $settings['plugins'][$pluginName]['active'])
	{
		$routes 		= Plugins::getNewRoutes($className, $routes);
		$middleware		= Plugins::getNewMiddleware($className, $middleware);
		
		$dispatcher->addSubscriber(new $className($container));
	}
}

# if plugins have been added or removed
if(isset($updateSettings))
{
	# update stored settings file
	$settingsModel->updateSettings($pluginSettings, 'plugins');
}

# add media extension to integrate video/audio with shortcodes
$dispatcher->addSubscriber(new MediaExtension($settings['rootPath'], $urlinfo['baseurl']));

# add final settings to the container
$container->set('settings', function() use ($settings){ return $settings; });

# dispatch the event onPluginsLoaded
$dispatcher->dispatch(new OnPluginsLoaded($plugins), 'onPluginsLoaded');

# dispatch settings event
$dispatcher->dispatch(new OnSettingsLoaded($settings), 'onSettingsLoaded');

$timer['plugins'] = microtime(true);


/****************************
* LOAD ROLES & PERMISSIONS	*
****************************/

# load roles and permissions and dispatch to plugins
$rolesAndPermissions = Permissions::loadRolesAndPermissions($settings['systemSettingsPath']);
$rolesAndPermissions = $dispatcher->dispatch(new OnRolesPermissionsLoaded($rolesAndPermissions), 'onRolesPermissionsLoaded')->getData();

# load resources and dispatch to plugins
$resources = Permissions::loadResources($settings['systemSettingsPath']);
$resources = $dispatcher->dispatch(new OnResourcesLoaded($resources), 'onResourcesLoaded')->getData();

# create acl-object
$acl = Permissions::createAcl($rolesAndPermissions, $resources);

# add acl to container
$container->set('acl', function() use ($acl){ return $acl; });

$timer['permissions'] = microtime(true);


/****************************
* SEGMENTS WITH SESSION			*
****************************/

# if website is restricted to registered user
if( ( isset($settings['access']) && $settings['access'] ) || ( isset($settings['pageaccess']) && $settings['pageaccess'] ) )
{
	# activate session for all routes
	$session_segments = [$urlinfo['route']];
}
else
{
	$session_segments = ['setup', 'tm/', 'api/'];

	# let plugins add own segments for session, eg. to enable csrf for forms
	$client_segments 	= $dispatcher->dispatch(new OnSessionSegmentsLoaded([]), 'onSessionSegmentsLoaded')->getData();
	$session_segments	= array_merge($session_segments, $client_segments);
}

# start session
# Session::startSessionForSegments($session_segments, $urlinfo['route']);

$timer['session segments'] = microtime(true);

# initialize globals
$TwigGlobals = ['errors' => NULL, 'flash' => NULL, 'assets' => NULL];
$TwigGlobals = $dispatcher->dispatch(new OnTwigGlobalsLoaded($TwigGlobals), 'onTwigGlobalsLoaded')->getData();

# echo '<pre>';
# print_r($TwigGlobals);
# die();

/****************************
* OTHER CONTAINER ITEMS			*
****************************/

# translations
$translations = Translations::loadTranslations($settings, $urlinfo['route']);
$container->set('translations', $translations);

# dispatcher to container
$container->set('dispatcher', function() use ($dispatcher){ return $dispatcher; });

# asset function for plugins
$assets = new \Typemill\Assets($urlinfo['baseurl']);
$container->set('assets', function() use ($assets){ return $assets; });

/****************************
* TWIG TO CONTAINER					*
****************************/

$container->set('view', function() use ($settings, $TwigGlobals, $urlinfo, $translations, $dispatcher) {

	$twig = Twig::create(
		[
			# path to templates with namespaces
			$settings['rootPath'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $settings['theme'],
			$settings['rootPath'] . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'typemill' . DIRECTORY_SEPARATOR . 'author',
		],
		[
			# settings
			'cache' => ( isset($settings['twigcache']) && $settings['twigcache'] ) ? $settings['rootPath'] . '/cache/twig' : false,
			'debug' => isset($settings['displayErrorDetails']),
			'debug' => true,
			'autoescape' => false
		]
	);

	foreach($TwigGlobals as $name => $feature)
	{
#		echo $name . ';';
		$twig->getEnvironment()->addGlobal($name, $feature);
	}

	# add extensions
	$twig->addExtension(new DebugExtension());
	$twig->addExtension(new TwigUserExtension());
	$twig->addExtension(new TwigUrlExtension($urlinfo));
	$twig->addExtension(new TwigLanguageExtension( $translations ));
	$twig->addExtension(new TwigMarkdownExtension($urlinfo['baseurl'], $settings, $dispatcher));
	$twig->addExtension(new TwigMetaExtension());
	$twig->addExtension(new TwigPagelistExtension());
	$twig->addExtension(new TwigCaptchaExtension());

	return $twig;

});

/****************************
* MIDDLEWARE				*
****************************/

foreach($middleware as $pluginMiddleware)
{
	$middlewareClass 	= $pluginMiddleware['classname'];
	$middlewareParams	= $pluginMiddleware['params'];
	if(class_exists($middlewareClass))
	{
		$app->add(new $middlewareClass($middlewareParams));
	}
}

$app->add(new CustomHeadersMiddleware($settings));

$app->add(new AssetMiddleware($assets, $container->get('view')));

$app->add(new ValidationErrorsMiddleware($container->get('view')));

$app->add(new SecurityMiddleware($routeParser, $container->get('settings')));

$app->add(new OldInputMiddleware($container->get('view')));

# Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

$app->add(new FlashMessages($container));

# add JsonBodyParser Middleware
$app->add(new JsonBodyParser());

# routing middleware earlier than error middleware so errors are shown
$app->addRoutingMiddleware();

# error middleware
$errorMiddleware = new ErrorMiddleware(
	$app->getCallableResolver(),
	$app->getResponseFactory(),
	$display_errors,
	false,
	false
);

# Set the Not Found Handler
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function ($request, $exception) use ($container) {
	
	$response = new NewResponse();

	return $container->get('view')->render($response->withStatus(404), '404.twig');

});

$app->add($errorMiddleware);

$app->add(new SessionMiddleware($session_segments, $urlinfo['route']));

$app->add(new RemoveCredentialsMiddleware());

if(isset($settings['proxy']) && $settings['proxy'])
{
	$trustedProxies = ( isset($settings['trustedproxies']) && !empty($settings['trustedproxies']) ) ? explode(",", $settings['trustedproxies']) : [];
	$app->add(new ProxyDetection($trustedProxies));	
}


$timer['middleware'] = microtime(true);


/******************************
* GET CSP FROM PLUGINS/THEMES *
******************************/

# get additional csp headers from plugins
$cspFromPlugins = $dispatcher->dispatch(new OnCspLoaded([]), 'onCspLoaded')->getData();

# get additional csp headers from theme 
$cspFromTheme = [];
$themeSettings = $settingsModel->getObjectSettings('themes', $settings['theme']);
if(isset($themeSettings['csp']) && is_array($themeSettings['csp']) && !empty($themeSettings['csp']) )
{
	$cspFromTheme = $themeSettings['csp'];
}

/************************
*   ADD ROUTES          *
************************/

if(isset($settings['setup']) && $settings['setup'] == true)
{
	require __DIR__ . '/routes/setup.php';
}
else
{
	require __DIR__ . '/routes/api.php';
	require __DIR__ . '/routes/web.php';
}

$timer['routes'] = microtime(true);


/************************
*   RUN APP         		*
************************/

$app->run();

# $timer['run'] = microtime(true);

# Typemill\Static\Helpers::printTimer($timer);