<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\StorageWrapper;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\Settings;
use Typemill\Static\Translations;

class ControllerWebSetup extends Controller
{
	public function show(Request $request, Response $response, $args)
	{
		# make some checks befor you install
		$storage = new StorageWrapper('\Typemill\Models\Storage');		
		$systemerrors = array();

		# check folders and create them if possible
		if( !$storage->checkFolder('settingsFolder'))
		{ 
			$systemerrors[] = $storage->getError(); 
		}
		if( !$storage->checkFolder('settingsFolder', 'users')){	$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('contentFolder')){ 			$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('dataFolder')){ 				$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('cacheFolder')){ 			$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('tmpFolder')){ 				$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('originalFolder')){ 			$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('liveFolder')){ 				$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('thumbsFolder')){ 			$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('customFolder')){ 			$systemerrors[] = $storage->getError(); }
		if( !$storage->checkFolder('fileFolder')){ 				$systemerrors[] = $storage->getError(); }

		# check php-version
		if (version_compare(phpversion(), '8.0.0', '<')) 
		{
			$systemerrors[] = 'The PHP-version of your server is ' . phpversion() . ' and Typemill needs at least 8.0.0';
		}

		# check if extensions are loaded
		if(!extension_loaded('gd')){ 		$systemerrors[] = 'The php-extension GD for image manipulation is not enabled.'; }
		if(!extension_loaded('mbstring')){ 	$systemerrors[] = 'The php-extension mbstring is not enabled.'; }
		if(!extension_loaded('fileinfo')){ 	$systemerrors[] = 'The php-extension fileinfo is not enabled.'; }
		if(!extension_loaded('session')){ 	$systemerrors[] = 'The php-extension session is not enabled.'; }
		if(!extension_loaded('iconv')){ 	$systemerrors[] = 'The php-extension iconv is not enabled.'; }

		$systemerrors = empty($systemerrors) ? false : $systemerrors;

	    return $this->c->get('view')->render($response, 'auth/setup.twig', [
	    	'systemerrors' => $systemerrors 
	    ]);
	}

	public function create(Request $request, Response $response, $args)
	{		
		$params 			= $request->getParsedBody();
		$params['userrole'] = 'administrator';
		$validate			= new Validation();
		$user				= new User();

		# get userroles for validation
		$userroles 			= $this->c->get('acl')->getRoles();

		# validate user
		if($validate->newSetupUser($params, $userroles) !== true)
		{
			$this->c->get('flash')->addMessage('error', Translations::translate('Please correct the errors in the form.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('setup.show'))->withStatus(302);
		}

		$userdata = [
				'username' 	=> $params['username'], 
				'email' 	=> $params['email'], 
				'userrole' 	=> $params['userrole'], 
				'password' 	=> $params['password']
		];

		$user = new User();
		
		# create initial user
		$username = $user->createUser($userdata);
					
		if($username)
		{
			usleep(30000);

			$user->setUser($username);

			$user->login();

			# create initial settings file
			$settingsModel = new Settings();
			$settingsModel->createSettings();

			$urlinfo = $this->c->get('urlinfo');
			$route = $urlinfo['baseurl'] . '/tm/system';

			usleep(30000);

			$this->c->get('flash')->addMessage('error', Translations::translate('Account created. Please login with your username and password now.'));
			
			return $response->withHeader('Location', $route)->withStatus(302);
		}

		$this->c->get('flash')->addMessage('error', Translations::translate('We could not create the user. Please check if the settings folde is writable.'));

		return $response->withHeader('Location', $this->routeParser->urlFor('setup.show'))->withStatus(302);
	}
}