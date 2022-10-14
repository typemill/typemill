<?php

# included from /public/index.php

use DI\Container;
use Slim\Middleware\ErrorMiddleware;
use Slim\Factory\AppFactory;
use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Psr7\Factory\UriFactory;
use Slim\Flash\Messages;
use Twig\Extension\DebugExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Typemill\Static\Settings;
use Typemill\Static\Plugins;
use Typemill\Static\Translations;
use Typemill\Static\Permissions;
use Typemill\Static\Session;
use Typemill\Events\OnSettingsLoaded;
use Typemill\Events\OnPluginsLoaded;
use Typemill\Events\OnSessionSegmentsLoaded;
use Typemill\Events\OnRolesPermissionsLoaded;
use Typemill\Events\OnResourcesLoaded;
use Typemill\Middleware\JsonBodyParser;
use Typemill\Middleware\FlashMessages;
use Typemill\Extensions\TwigCsrfExtension;
use Typemill\Extensions\TwigUrlExtension;
use Typemill\Extensions\TwigUserExtension;
use Typemill\Models\StorageWrapper;

# require __DIR__  . '/../vendor/autoload.php';

$timer = [];
$timer['start'] = microtime(true);

/****************************
* HIDE ERRORS BY DEFAULT    *
****************************/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/****************************
* LOAD SETTINGS							*
****************************/

$settings = Settings::loadSettings();

/****************************
* HANDLE DISPLAY ERRORS 	  *
****************************/

if(isset($settings['displayErrorDetails']) && $settings['displayErrorDetails'])
{
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);	
}

/****************************
* ADD PATH-INFOS FOR LATER 	*
****************************/

# ADD THEM TO THE SETTINGS AND YOU HAVE THEM EVERYWHERE??
$uriFactory 						= new UriFactory();
$uri 										= $uriFactory->createFromGlobals($_SERVER);

$settings['fullpath'] 	= $uri->getPath();
$settings['basepath'] 	= preg_replace('/(.*)\/.*/', '$1', $_SERVER['SCRIPT_NAME']);
$settings['routepath'] 	= str_replace($settings['basepath'], '', $settings['fullpath']);

$timer['settings'] = microtime(true);

/****************************
* CREATE CONTAINER      		*
****************************/

# https://www.slimframework.com/docs/v4/start/upgrade.html#changes-to-container
$container 				= new Container();
AppFactory::setContainer($container);

$app 							= AppFactory::create();
$container 				= $app->getContainer();

$responseFactory 	= $app->getResponseFactory();
$routeParser 			= $app->getRouteCollector()->getRouteParser();

# add route parser to container to use named routes in controller
$container->set('routeParser', $routeParser);

# in slim 4 you alsways have to set application basepath
$app->setBasePath($settings['basepath']);

$timer['container'] = microtime(true);


/****************************
* CREATE EVENT DISPATCHER		*
****************************/

$dispatcher = new EventDispatcher();


/****************************
* LOAD & UPDATE PLUGINS			*
****************************/

$plugins 					= Plugins::loadPlugins();
$routes 					= [];
$middleware				= [];

# if there are less plugins in the scan than in the settings, then a plugin has been removed
if(count($plugins) < count($settings['plugins']))
{
	$updateSettings = true;
}

foreach($plugins as $plugin)
{
	$pluginName			= $plugin['name'];
	$className			= $plugin['className'];

	# if plugin is not in the settings already
	if(!isset($settings['plugins'][$pluginName]))
	{
		# it is a new plugin. Add it and set active to false
		$settings['plugins'][$pluginName] = ['active' => false];
		
		# and set flag to refresh the settings
		$updateSettings = true;
	}

	# if the plugin is activated, add routes/middleware and add plugin as event subscriber
	if($settings['plugins'][$pluginName]['active'])
	{
		$routes 			= Plugins::getNewRoutes($className, $routes);
		$middleware		= Plugins::getNewMiddleware($className, $middleware);
		
		$dispatcher->addSubscriber(new $className($container));
	}
}

# if plugins have been added or removed
if(isset($updateSettings))
{	
	# update stored settings file
	Settings::updateSettings($settings);
}

# add final settings to the container
$container->set('settings', function() use ($settings){ return $settings; });

# dispatch the event onPluginsLoaded
$dispatcher->dispatch(new OnPluginsLoaded($plugins), 'onPluginsLoaded');

# dispatch settings event and get all setting-updates from plugins
$dispatcher->dispatch(new OnSettingsLoaded($settings), 'onSettingsLoaded')->getData();

$timer['plugins'] = microtime(true);


/****************************
* LOAD ROLES & PERMISSIONS	*
****************************/

# load roles and permissions and dispatch to plugins
$rolesAndPermissions = Permissions::loadRolesAndPermissions($settings['defaultSettingsPath']);
$rolesAndPermissions = $dispatcher->dispatch(new OnRolesPermissionsLoaded($rolesAndPermissions), 'onRolesPermissionsLoaded')->getData();

# load resources and dispatch to plugins
$resources = Permissions::loadResources($settings['defaultSettingsPath']);
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
	$session_segments = [$settings['routepath']];
}
else
{
	$session_segments = ['setup', 'tm/'];

	# let plugins add own segments for session, eg. to enable csrf for forms
	$client_segments 	= $dispatcher->dispatch(new OnSessionSegmentsLoaded([]), 'onSessionSegmentsLoaded')->getData();
	$session_segments	= array_merge($session_segments, $client_segments);
}

# start session
Session::startSessionForSegments($session_segments, $settings['routepath']);

$timer['session segments'] = microtime(true);

/****************************
* OTHER CONTAINER ITEMS			*
****************************/

# dispatcher to container
$container->set('dispatcher', function() use ($dispatcher){ return $dispatcher; });

# asset function for plugins
$container->set('assets', function() use ($settings){ return new \Typemill\Assets($settings['basepath']); });

# Register Middleware On Container
$csrf = false;
if(isset($_SESSION))
{
	# add flash messsages
	$container->set('flash', function(){ return new Messages(); });

	# Register Middleware On Container
	$csrf = new Guard($responseFactory);
	$container->set('csrf', function () use ($csrf){ return $csrf; });

	# Add Validation Errors Middleware
	# $app->add(new ValidationErrors($container->get('view')));
}


/****************************
* TWIG TO CONTAINER					*
****************************/

$container->set('view', function() use ($settings, $csrf, $uri) {
	
	$twig = Twig::create(
		[
			# path to templates
			$settings['rootPath'] . $settings['authorFolder'],
			$settings['rootPath'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $settings['theme'],
		],
		[
			# settings
			'cache' => ( isset($settings['twigcache']) && $settings['twigcache'] ) ? $settings['rootPath'] . '/cache/twig' : false,
			'debug' => isset($settings['displayErrorDetails'])
		]
	);
	
	$twig->getEnvironment()->addGlobal('errors', NULL);
	$twig->getEnvironment()->addGlobal('flash', NULL);

	# add extensions
	$twig->addExtension(new DebugExtension());
	$twig->addExtension(new TwigUserExtension());
	$twig->addExtension(new TwigUrlExtension($uri, $settings['basepath']));

	# $twig->addExtension(new \Nquire\Extensions\TwigUserExtension());

	if($csrf)
	{
		$twig->addExtension(new TwigCsrfExtension($csrf));
	}

	# translations
	$translations = Translations::loadTranslations($settings);
	# $twig->getEnvironment()->addGlobal('translations', $translations);
	$twig->addExtension(new Typemill\Extensions\TwigLanguageExtension( $translations ));

	return $twig;

});


/****************************
* MIDDLEWARE								*
****************************/

if(isset($_SESSION))
{
	# Add Validation Errors Middleware
	#$app->add(new ValidationErrors($container->get('view')));

	# Add Flash Messages Middleware
	$app->add(new FlashMessages($container->get('view')));

	# Add csrf middleware globally
	$app->add('csrf');
}

# Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

# add JsonBodyParser Middleware
$app->add(new JsonBodyParser());

# routing middleware earlier than error middleware so errors are shown
$app->addRoutingMiddleware();

# error middleware
$errorMiddleware = new ErrorMiddleware(
	$app->getCallableResolver(),
	$app->getResponseFactory(),
	true,
	false,
	false
);

# Set the Not Found Handler
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function ($request, $exception) use ($container) {
	
	$response = new NewResponse();

	return $container->get('view')->render($response->withStatus(404), '404.twig');

});

$app->add($errorMiddleware);

$timer['middleware'] = microtime(true);

/************************
*   ADD ROUTES          *
************************/

require __DIR__ . '/routes/api.php';
require __DIR__ . '/routes/web.php';

$timer['routes'] = microtime(true);

/************************
*   RUN APP         		*
************************/

$app->run();

$timer['run'] = microtime(true);

# Typemill\Static\Helpers::printTimer($timer);

die();
die('After app run');



























/********************************
*  MOVE TO MIDDLEWARE NEXT TIME *
********************************/

print_r($session_segments);

$trimmedRoute = ltrim($routepath,'/');

foreach($session_segments as $segment)
{

  $test = substr( $trimmedRoute, 0, strlen($segment) );

  # echo '<br>' . $test . ' = ' . $segment;
#  continue;

	if(substr( $uri->getPath(), 0, strlen($segment) ) === ltrim($segment, '/'))
	{	
		// configure session
		ini_set('session.cookie_httponly', 1 );
		ini_set('session.use_strict_mode', 1);
		ini_set('session.cookie_samesite', 'lax');
		if($uri->getScheme() == 'https')
		{
			ini_set('session.cookie_secure', 1);
			session_name('__Secure-typemill-session');
		}
		else
		{
			session_name('typemill-session');
		}
		
		// add csrf-protection
		$container['csrf'] = function ($c)
		{
			$guard = new \Slim\Csrf\Guard();
			$guard->setPersistentTokenMode(true);
			$guard->setfailurecallable(function ($request, $response, $next)
			{
				$request = $request->withattribute("csrf_result", false);
				return $next($request, $response);
			});

			return $guard;
		};
		
		// add flash to container
		$container['flash'] = function () 
		{
			return new \Slim\Flash\Messages();
		};
		
		// start session
		session_start();
	}
}



Typemill\Static\Helpers::printTimer($timer);

die('Typemill 2 is comming');








# add flash messsages
$container->set('flash', function(){
	return new Messages();
});

# Register Middleware On Container
$container->set('csrf', function () use ($responseFactory) {
	return new Guard($responseFactory);
});

# Set view in Container
$container->set('view', function() use ($container) {
	
	$twig = Twig::create(__DIR__ . DIRECTORY_SEPARATOR . 'views',['cache' => false, 'debug' => true]);
	
	$twig->getEnvironment()->addGlobal('errors', NULL);
	$twig->getEnvironment()->addGlobal('flash', NULL);

	$twig->addExtension(new \Twig\Extension\DebugExtension());
	$twig->addExtension(new \Nquire\Extensions\TwigUserExtension());
	$twig->addExtension(new \Nquire\Extensions\TwigCsrfExtension($container->get('csrf')));

	return $twig;
});
 
/****************************
*     SET ROUTE PARSER TO USE NAMED ROUTES IN CONTROLLER            *
****************************/

$container->set('routeParser', $routeParser);

/****************************
*    MIDDLEWARE           *
****************************/

# Add Validation Errors Middleware
$app->add(new ValidationErrors($container->get('view')));

# Add Flash Messages Middleware
$app->add(new FlashMessages($container->get('view')));

# Add csrf middleware globally
$app->add('csrf');

# Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

# add JsonBodyParser Middleware
$app->add(new JsonBodyParser());

/**
  * The routing middleware should be added earlier than the ErrorMiddleware
  * Otherwise exceptions thrown from it will not be handled by the middleware
  */
$app->addRoutingMiddleware();

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger  
 *
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */

# $errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware = new ErrorMiddleware(
	$app->getCallableResolver(),
	$app->getResponseFactory(),
	true,
	false,
	false
);

# Set the Not Found Handler
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function ($request, $exception) use ($container) {
	
	$response = new NewResponse();

	return $container->get('view')->render($response->withStatus(404), 'errors/404.twig');

});

$app->add($errorMiddleware);

/*

# Set the Not Found Handler
$errorMiddleware->setErrorHandler(
	HttpNotFoundException::class,
	function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails) {
		$response = new Response();
		$response->getBody()->write('404 NOT FOUND');

		return $response->withStatus(404);
	}
);

# Set the Not Allowed Handler
$errorMiddleware->setErrorHandler(
	HttpMethodNotAllowedException::class,
	function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails) {
		$response = new Response();
		$response->getBody()->write('405 NOT ALLOWED');

		return $response->withStatus(405);
	}
);

# Set the Not Found Handler
$errorMiddleware->setErrorHandler(
	HttpNotFoundException::class,
	function () {
		die('not found');
	}
);

$app->add($ErrorMiddleware);

*/

/************************
*   ADD ROUTES          *
************************/

require __DIR__ . '/routes/api.php';
require __DIR__ . '/routes/web.php';

$app->run();