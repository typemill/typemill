<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\Write;

class SetupController extends Controller
{
	public function show($request, $response, $args)
	{
		/* make some checks befor you install */
		$checkFolder = new Write();
		
		$systemcheck = array();
		
		try{ $checkFolder->checkPath('settings'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('settings/users'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('content'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }
		try{ $checkFolder->checkPath('cache'); }catch(\Exception $e){ $systemcheck['error'][] = $e->getMessage(); }

		$systemcheck = empty($systemcheck) ? false : $systemcheck;

		return $this->render($response, 'auth/setup.twig', array( 'messages' => $systemcheck ));
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

					/* store updated settings */
					$settings = $this->c->get('settings');
					$settings->replace(['setup' => false]);
									
					/* store updated settings */
					\Typemill\Settings::updateSettings(array('setup' => false));

					return $this->render($response, 'auth/welcome.twig', array());
				}
			}
			
			$this->c->flash->addMessage('error', 'Please check your input and try again');
			return $response->withRedirect($this->c->router->pathFor('setup.show'));
		}
	}
}