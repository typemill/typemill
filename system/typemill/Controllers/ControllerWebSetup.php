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
		$systemerrors = [];

		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'settingsFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'settingsFolder', 'users');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'contentFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'dataFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'cacheFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'tmpFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'originalFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'liveFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'thumbsFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'customFolder');
		$systemerrors = $this->checkAndCreateFolder($storage, $systemerrors, 'fileFolder');

		# check php-version
		if (version_compare(phpversion(), '8.2.0', '<')) 
		{
			$systemerrors[] = 'The PHP-version of your server is ' . phpversion() . ' and Typemill needs at least 8.2.0';
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

	private function checkAndCreateFolder($storage, array $systemerrors, string $foldername, $subfoldername = NULL)
	{
		if(!$storage->checkFolder($foldername, $subfoldername))
		{
			if(!$storage->createFolder($foldername, $subfoldername))
			{
				$systemerrors[] = $storage->getError();
			}
		}

		return $systemerrors;
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
			$this->c->get('flash')->addMessage('error', Translations::translate('Please correct your input.'));

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
			# create initial settings file
			$settingsModel = new Settings();
			$settingsModel->createSettings([
				'author' 		=> $params['username'],
				'mailfrom'		=> $params['email'],
				'mailfromname'	=> $params['username']
			]);

			$user->setUser($username);

			$user->login();

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