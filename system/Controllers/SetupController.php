<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\Write;

class SetupController extends Controller
{

	# redirect if visit /setup route
	public function redirect($request, $response)
	{
		return $response->withRedirect($this->c->router->pathFor('setup.show'));
	}

	public function show($request, $response, $args)
	{
		/* make some checks befor you install */
		$checkFolder = new Write();
		
		$systemcheck = array();

		# check folders and create them if possible		
		try{ $checkFolder->checkPath('settings'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('settings/users'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('content'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('cache'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('media'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }


		# check php-version
		if (version_compare(phpversion(), '7.0.0', '<')) {
				$systemcheck['error'][] = 'The PHP-version of your server is ' . phpversion() . ' and Typemill needs at least 7.0.0';
		}

		/* check if mod rewrite is enabled, does not work with PHP-fpm or NGINX
		$modules = apache_get_modules();
		if(!in_array('mod_rewrite', $modules))
		{
			$systemcheck['error'][] = 'The apache module "mod_rewrite" is not enabled.';
		}
		*/

		# check if GD  extension is enabled
		if(!extension_loaded('gd')){
			$systemcheck['error'][] = 'The php-extension GD for image manipulation is not enabled.';
		}

		$setuperrors = empty($systemcheck) ? false : 'Some system requirements for Typemill are missing.';
		$systemcheck = empty($systemcheck) ? false : $systemcheck;

		return $this->render($response, 'auth/setup.twig', array( 'messages' => $setuperror, 'systemcheck' => $systemcheck ));
	}

	public function create($request, $response, $args)
	{
		if($request->isPost())
		{
			$params 		= $request->getParams();
			$validate		= new Validation();
			$user			= new User();

			/* set user as admin */
			$params['userrole'] = 'administrator';
			
			/* get userroles for validation */
			$userroles		= $user->getUserroles();
			
			/* validate user */
			if($validate->newUser($params, $userroles))
			{
				$userdata = array('username' => $params['username'], 'email' => $params['email'], 'userrole' => $params['userrole'], 'password' => $params['password']);
				
				/* create initial user */
				$username = $user->createUser($userdata);
				
				if($username)
				{
					/* login user */
					$user->login($username);

					# create initial settings file
					\Typemill\Settings::createSettings();
					
					return $response->withRedirect($this->c->router->pathFor('setup.welcome'));
				}
			}
			
			$this->c->flash->addMessage('error', 'Please check your input and try again');
			return $response->withRedirect($this->c->router->pathFor('setup.show'));
		}
	}
	
	public function welcome($request, $response, $args)
	{
		/* store updated settings */
		\Typemill\Settings::updateSettings(array('welcome' => false));
		
		return $this->render($response, 'auth/welcome.twig', array());		
	}
}