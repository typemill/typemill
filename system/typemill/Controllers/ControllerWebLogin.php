<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use Slim\Views\Twig;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\WriteYaml;
use Typemill\Extensions\ParsedownExtension;

class ControllerWebLogin extends ControllerWeb
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
	    return $this->c->get('view')->render($response, 'login.twig', [
			#'captcha' => $this->checkIfAddCaptcha(),
			#'url' => $this->urlCollection,
	    ]);

#		$settings = $this->c->get('settings');

#		return $this->render($response, '/auth/login.twig', ['settings' => $settings]);
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
	    if( ( null !== $request->getattribute('csrf_result') ) OR ( $request->getattribute('csrf_result') === false ) )
	    {
			$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
	    }

		/* authentication */
		$params	 		= $request->getParams();
		$validation		= new Validation();
		$settings 		= $this->c->get('settings');
		
		if($validation->signin($params))
		{
			$user = new User();
			$userdata = $user->getUser($params['username']);

			if($userdata && password_verify($params['password'], $userdata['password']))
			{
				# check if user has confirmed the account 
				if(isset($userdata['optintoken']) && $userdata['optintoken'])
				{
					$this->c->flash->addMessage('error', 'Your registration is not confirmed yet. Please check your e-mails and use the confirmation link.');
					return $response->withRedirect($this->c->router->pathFor('auth.show'));				
				}

				$user->login($userdata['username']);

				# if user is allowed to view content-area
				if($this->c->acl->hasRole($userdata['userrole']) && $this->c->acl->isAllowed($userdata['userrole'], 'content', 'view'))
				{
					$settings = $this->c->get('settings');
					$editor = (isset($settings['editor']) && $settings['editor'] == 'visual') ? 'visual' : 'raw';
					
					return $response->withRedirect($this->c->router->pathFor('content.' . $editor));
				}
				return $response->withRedirect($this->c->router->pathFor('user.account'));
			}
		}
		
		if(isset($this->settings['securitylog']) && $this->settings['securitylog'])
		{
			\Typemill\Models\Helpers::addLogEntry('wrong login');
		}

		$this->c->flash->addMessage('error', 'Ups, wrong password or username, please try again.');
		return $response->withRedirect($this->c->router->pathFor('auth.show'));
	}
}