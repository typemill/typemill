<?php

namespace Typemill\Controllers;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Validation;
use Typemill\Models\User;

class AuthController extends Controller
{
	
	public function redirect(Request $request, Response $response)
	{
		if(isset($_SESSION['login']))
		{
			return $response->withRedirect($this->c->router->pathFor('settings.show'));
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
		$this->c->view->render($response, '/auth/login.twig');
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
		$params	 		= $request->getParams();
		$validation		= new Validation();
		
		if($validation->signin($params))
		{
			$user = new User();
			$userdata = $user->getUser($params['username']);
			
			if($userdata && password_verify($params['password'], $userdata['password']))
			{
				$user->login($userdata['username']);
				return $response->withRedirect($this->c->router->pathFor('settings.show'));
			}
		}
		
		$this->c->flash->addMessage('error', 'Ups, credentials were wrong, please try again.');
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
}