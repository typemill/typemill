<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Validation;
use Typemill\Models\User;

class ControllerWebAuth extends Controller
{
	public function show(Request $request, Response $response)
	{
	    return $this->c->get('view')->render($response, 'auth/login.twig', [
			#'captcha' => $this->checkIfAddCaptcha(),
	    ]);
	}
	
	public function login(Request $request, Response $response)
	{
	    if( ( null !== $request->getattribute('csrf_result') ) OR ( $request->getattribute('csrf_result') === false ) )
	    {
			$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'));
	    }

        $input 			= $request->getParsedBody();
		$validation		= new Validation();
#		$settings 		= $this->c->get('settings');
		
		if($validation->signin($input))
		{
			$user = new User();

			if(!$user->setUserWithPassword($input['username']))
			{
				$this->c->get('flash')->addMessage('error', 'Ups, wrong password or username, please try again!!');

				return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
			}

			$userdata = $user->getUserData();

			if($userdata && password_verify($input['password'], $userdata['password']))
			{				
				# check if user has confirmed the account 
				if(isset($userdata['optintoken']) && $userdata['optintoken'])
				{
					$this->c->get('flash')->addMessage('error', 'Your registration is not confirmed yet. Please check your e-mails and use the confirmation link.');
					return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
				}

				$user->login();

return $response->withHeader('Location', $this->routeParser->urlFor('settings.show'))->withStatus(302);

/*
				# if user is allowed to view content-area
				$acl = $this->c->get('acl');
				if($acl->hasRole($userdata['userrole']) && $acl->isAllowed($userdata['userrole'], 'content', 'view'))
				{
					$editor = (isset($this->settings['editor']) && $this->settings['editor'] == 'visual') ? 'visual' : 'raw';
					
					return $response->withHeader('Location', $this->routeParser->urlFor('content.' . $editor))->withStatus(302);
				}

				return $response->withHeader('Location', $this->routeParser->urlFor('user.account'))->withStatus(302);
*/
			}
		}
		
		if(isset($this->settings['securitylog']) && $this->settings['securitylog'])
		{
			\Typemill\Static\Helpers::addLogEntry('wrong login');
		}

		$this->c->get('flash')->addMessage('error', 'Ups, wrong password or username, please try again.');

		return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
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
		\Typemill\Static\Session::stopSession();

		return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
	}
}