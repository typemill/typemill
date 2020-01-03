<?php

use Typemill\Events\OnSettingsLoaded;
use Typemill\Events\OnPluginsLoaded;
use Typemill\Events\OnSessionSegmentsLoaded;


// i18n
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;


/****************************
* HIDE ERRORS BY DEFAULT	  *
****************************/

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/****************************
* CREATE EVENT DISPATCHER	*
****************************/

$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

/************************
* LOAD SETTINGS			*
************************/

$settings = Typemill\Settings::loadSettings();

/****************************
* HANDLE DISPLAY ERRORS 	  *
****************************/

if($settings['settings']['displayErrorDetails'])
{
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);	
}

/************************
* INITIATE SLIM 		*
************************/

$app = new \Slim\App($settings);

/************************
*  GET SLIM CONTAINER	*
************************/

$container = $app->getContainer();

/************************
* LOAD & UPDATE PLUGINS *
************************/

$plugins 		= new Typemill\Plugins();
$pluginNames	= $plugins->load();
$pluginSettings = $routes = $middleware	= array();

foreach($pluginNames as $pluginName)
{
	$className	= $pluginName['className'];
	$name		= $pluginName['name'];
		
	# check if plugin is in the settings already
	if(isset($settings['settings']['plugins'][$name]))
	{
		# if so, add the settings to temporary plugin settings
		$pluginSettings[$name] = $settings['settings']['plugins'][$name];
		
		# and delete them from original settings
		unset($settings['settings']['plugins'][$name]);
	}
	else
	{
		# if not, it is a new plugin. Add it and set active to false
		$pluginSettings[$name] = ['active' => false];
		
		# and set flag to refresh the settings
		$refreshSettings = true;
	}
	
	# if the plugin is activated, add routes/middleware and add plugin as event subscriber
	if($pluginSettings[$name]['active'])
	{
		$routes 		= $plugins->getNewRoutes($className, $routes);
		$middleware		= $plugins->getNewMiddleware($className, $middleware);
		
		$dispatcher->addSubscriber(new $className($container));
	}
}

# if plugins in original settings are not empty now, then a plugin has been removed
if(!empty($settings['settings']['plugins'])){ $refreshSettings = true; }

# update the settings in all cases
$settings['settings']['plugins'] = $pluginSettings;

# if plugins have been added or removed
if(isset($refreshSettings))
{
	# update the settings in the container
	$container->get('settings')->replace($settings['settings']);
	
	# update stored settings file
	$refreshSettings = Typemill\settings::updateSettings($settings['settings']);
}

# dispatch the event onPluginsLoaded
$dispatcher->dispatch('onPluginsLoaded', new OnPluginsLoaded($pluginNames));

# dispatch settings event and get all setting-updates from plugins
$dispatcher->dispatch('onSettingsLoaded', new OnSettingsLoaded($settings))->getData();


/******************************
* ADD DISPATCHER TO CONTAINER *
******************************/

$container['dispatcher'] = function($container) use ($dispatcher)
{
	return $dispatcher;
};


/********************************
* ADD ASSET-FUNCTION FOR TWIG	*
********************************/

$container['assets'] = function($c)
{
	return new \Typemill\Assets($c['request']->getUri()->getBaseUrl());
};


/************************
* 	DECIDE FOR SESSION	*
************************/

$session_segments 	= array('setup', 'tm/', 'api/', '/setup', '/tm/', '/api/');

# let plugins add own segments for session, eg. to enable csrf for forms
$client_segments 	= $dispatcher->dispatch('onSessionSegmentsLoaded', new OnSessionSegmentsLoaded([]))->getData();
$session_segments	= array_merge($session_segments, $client_segments);

$path 				= $container['request']->getUri()->getPath();
$container['flash']	= false;
$container['csrf'] 	= false;

foreach($session_segments as $segment)
{
	if(substr( $path, 0, strlen($segment) ) === $segment)
	{	
		// configure session
		ini_set('session.cookie_httponly', 1 );
		ini_set('session.use_strict_mode', 1);
		if($container['request']->getUri()->getScheme() == 'https')
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

/************************
* 	LOAD TWIG VIEW		*
************************/

$container['view'] = function ($container)
{
	$path = array($container->get('settings')['themePath'], $container->get('settings')['authorPath']);
	
    $view = new \Slim\Views\Twig( $path, [
		'cache' => false,
		'autoescape' => false,
		'debug' => true
    ]);


    // i18n
    // get language from setting, but in case of setup, detecting browser language
    $language = ( $container->get('settings')['setup'] ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : $container->get('settings')['language']);
    
    $fallbackLanguage = 'en'; // language used if the definition is not present in the requested language
    $translator = new Translator( $language ); // constructor
    $translator->setFallbackLocales([ $fallbackLanguage ]); // set the fallback

    // loading messages with the yaml file loaders
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', './translations/en.yaml', 'en');
    if( $fallbackLanguage != $language ) $translator->addResource('yaml', './translations/'.$language.'.yaml', $language);


    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
	$view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new Typemill\Extensions\TwigUserExtension());
	$view->addExtension(new Typemill\Extensions\TwigMarkdownExtension());	
	$view->addExtension(new Typemill\Extensions\TwigMetaExtension());	
	


  // i18n
  $view->addExtension(new TranslationExtension($translator));
  

	/* use {{ base_url() }} in twig templates */
	$view['base_url']	 = $container['request']->getUri()->getBaseUrl();
	$view['current_url'] = $container['request']->getUri()->getPath();
	
	/* if session route, add flash messages and csrf-protection */
	if($container['flash'])
	{
		$view->getEnvironment()->addGlobal('flash', $container->flash);
		$view->addExtension(new Typemill\Extensions\TwigCsrfExtension($container['csrf']));
	}

	/* add asset-function to all views */
	$view->getEnvironment()->addGlobal('assets', $container->assets);

	return $view;
};

$container->dispatcher->dispatch('onTwigLoaded');

/***************************
* 	ADD NOT FOUND HANDLER  *
***************************/

$container['notFoundHandler'] = function($c)
{
	return new \Typemill\Handlers\NotFoundHandler($c['view']);
};

/************************
* 	ADD MIDDLEWARE  	*
************************/

foreach($middleware as $pluginMiddleware)
{
	$middlewareClass 	= $pluginMiddleware['classname'];
	$middlewareParams	= $pluginMiddleware['params'];
	if(class_exists($middlewareClass))
	{
		$app->add(new $middlewareClass($middlewareParams));
	}
}

if($container['flash'])
{
	$app->add(new \Typemill\Middleware\ValidationErrorsMiddleware($container['view']));
	$app->add(new \Typemill\Middleware\OldInputMiddleware($container['view']));
	$app->add($container->get('csrf'));	
}

/************************
* 	ADD ROUTES			*
************************/

require __DIR__ . '/Routes/Api.php';
require __DIR__ . '/Routes/Web.php';