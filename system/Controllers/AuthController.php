<?php

namespace Typemill\Controllers;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\WriteYaml;

class AuthController extends Controller
{
	# redirect if visit /setup route
	public function redirect(Request $request, Response $response)
	{
		if(isset($_SESSION['login']))
		{
			return $response->withRedirect($this->c->router->pathFor('content.raw'));
		}
		else
		{
			return $response->withRedirect($this->c->router->pathFor('auth.show'));			
		}
	}

	/**
	* show login form
	* 
	* @param obj $request the slim request object.
	* @param obj $response the slim response object.
	* @param array $args with arguments past to the slim router
	* @return obj $response and string route.
	*/
	
	public function show(Request $request, Response $response, $args)
	{	
		$data 			= array();

		/* check previous login attemps */		
		$yaml 			= new WriteYaml();
		$logins 		= $yaml->getYaml('settings/users', '.logins');
		$userIP 		= $this->getUserIP();
		$userLogins		= isset($logins[$userIP]) ? count($logins[$userIP]) : false;
		
		if($userLogins)
		{
			/* get the latest */
			$lastLogin = intval($logins[$userIP][$userLogins-1]);
			
			/* if last login is longer than 60 seconds ago, clear it. */
			if(time() - $lastLogin > 60)
			{
				unset($logins[$userIP]);
				$yaml->updateYaml('settings/users', '.logins', $logins);
			}
			
			/* Did the user made three login attemps that failed? */
			elseif($userLogins >= 3)
			{
				$timeleft 			= 60 - (time() - $lastLogin);
				$data['messages'] 	= array('time' => $timeleft, 'error' => array( 'Too many bad logins. Please wait.')); 
			}
		}

		return $this->render($response, '/auth/login.twig', $data);
	}
	
	/**
	* signin an existing user
	* 
	* @param obj $request the slim request object with form data in the post params.
	* @param obj $response the slim response object.
	* @return obj $response with redirect to route.
	*/
	
	public function login(Request $request, Response $response)
	{
		/* log user attemps to authenticate */
		$yaml 			= new WriteYaml();
		$logins 		= $yaml->getYaml('settings/users', '.logins');
		$userIP 		= $this->getUserIP();
		$userLogins		= isset($logins[$userIP]) ? count($logins[$userIP]) : false;

		/* if there have been user logins before. You have to do this again, because user does not always refresh the login page and old login attemps are stored. */
		if($userLogins)
		{
			/* get the latest */
			$lastLogin = intval($logins[$userIP][$userLogins-1]);
			
			/* if last login is longer than 60 seconds ago, clear it and add this attempt */
			if(time() - $lastLogin > 60)
			{
				unset($logins[$userIP]);
				$yaml->updateYaml('settings/users', '.logins', $logins);
			}
			
			/* Did the user made three login attemps that failed? */
			elseif($userLogins >= 2)
			{
				$logins[$userIP][] = time();
				$yaml->updateYaml('settings/users', '.logins', $logins);
				
				return $response->withRedirect($this->c->router->pathFor('auth.show'));
			}	
		}

		/* authentication */		
		$params	 		= $request->getParams();
		$validation		= new Validation();
		
		if($validation->signin($params))
		{
			$user = new User();
			$userdata = $user->getUser($params['username']);

			if($userdata && password_verify($params['password'], $userdata['password']))
			{
				$user->login($userdata['username']);

				/* clear the user login attemps */
				if($userLogins)
				{
					unset($logins[$userIP]);
					$yaml->updateYaml('settings/users', '.logins', $logins);					
				}

				# if user is allowed to view content-area
				if($this->c->acl->isAllowed($userdata['userrole'], 'content', 'view'))
				{
					$settings = $this->c->get('settings');
					$editor = (isset($settings['editor']) && $settings['editor'] == 'visual') ? 'visual' : 'raw';
					
					return $response->withRedirect($this->c->router->pathFor('content.' . $editor));
				}
				return $response->withRedirect($this->c->router->pathFor('user.account'));
			}
		}

		/* if authentication failed, add attempt to log file */
		$logins[$userIP][] = time();
		$yaml->updateYaml('settings/users', '.logins', $logins);
		
		$this->c->flash->addMessage('error', 'Ups, wrong password or username, please try again.');
		return $response->withRedirect($this->c->router->pathFor('auth.show'));
	}
	
	/**
	* log out a user
	* 
	* @param obj $request the slim request object
	* @param obj $response the slim response object
	* @return obje $response with redirect to route
	*/
	
	public function logout(Request $request, Response $response)
	{
		if(isset($_SESSION))
		{
			session_destroy();
		}
		
		return $response->withRedirect($this->c->router->pathFor('auth.show'));
	}

	private function getUserIP()
	{
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if(filter_var($client, FILTER_VALIDATE_IP))
		{
			$ip = $client;
		}
		elseif(filter_var($forward, FILTER_VALIDATE_IP))
		{
			$ip = $forward;
		}
		else
		{
			$ip = $remote;
		}

		return $ip;
	}
}