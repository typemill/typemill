<?php
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Response as NewResponse;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Csrf\Guard;
use Slim\Flash\Messages;
use Nquire\Middleware\ValidationErrors;
use Nquire\Middleware\FlashMessages;
use Nquire\Middleware\JsonBodyParser;

require __DIR__  . '/../vendor/autoload.php';

/****************************
* HIDE ERRORS BY DEFAULT      *
****************************/

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/****************************
*      CONTAINER            *
****************************/

# https://www.slimframework.com/docs/v4/start/upgrade.html#changes-to-container

$container 				= new Container();
AppFactory::setContainer($container);
$app 							= AppFactory::create();
$container 				= $app->getContainer();

$responseFactory 	= $app->getResponseFactory();
$routeParser 			= $app->getRouteCollector()->getRouteParser();

/****************************
*     BASE PATH            *
****************************/

# basepath must always be set in slim 4
$basepath = preg_replace('/(.*)\/.*/', '$1', $_SERVER['SCRIPT_NAME']);

$container->set('basePath', $basepath);

$app->setBasePath($basepath);

die('hello Typemill V2');


/****************************
*     SETTINGS            	*
****************************/
$settings = require __DIR__ . '/settings/settings.php';

$container->set('settings', function() use ($settings)
{
	return $settings;
});


# create a session
ini_set('session.cookie_httponly', 1 );
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'lax');
if(isset($_SERVER['HTTPS']))
{
	ini_set('session.cookie_secure', 1);
	session_name('__Secure-nquire-session');
}
else
{
	session_name('nquire-session');	
}
session_start();

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