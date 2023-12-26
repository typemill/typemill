<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\SimpleMail;
use Typemill\Static\Translations;

class ControllerWebAuth extends Controller
{
	public function show(Request $request, Response $response)
	{
	    return $this->c->get('view')->render($response, 'auth/login.twig', [
			'recover' 		=> $this->settings['recoverpw'] ?? false,
			'captcha' 		=> $this->settings['authcaptcha'] ?? false,
	    ]);
	}
	
	public function login(Request $request, Response $response)
	{
        $input 			= $request->getParsedBody();
		$validation		= new Validation();
		$securitylog 	= $this->settings['securitylog'] ?? false;
		$authcodeactive = $this->settings['authcode'] ?? false;
		
		if($validation->signin($input) !== true)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: invalid data');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}
		
		$user = new User();

		if(!$user->setUserWithPassword($input['username']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not found');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$userdata 		= $user->getUserData();
		$authcodedata 	= $this->checkAuthcode($userdata);

		if($userdata && !password_verify($input['password'], $userdata['password']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: wrong password');
			}

			# always show authcode page, so attacker does not know if email or password was wrong or mail was send.
			if($authcodeactive && !$authcodedata['valid'])
			{
				# a bit slower because send mail takes some time usually
				usleep(rand(100000, 200000));

				# show authcode page
			    return $this->c->get('view')->render($response, 'auth/authcode.twig', [
					'username' 		=> $userdata['username'],
			    ]);
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		# check device fingerprint
		if($authcodeactive)
		{
			$fingerprint = $this->generateDeviceFingerprint();
			if(!$this->findDeviceFingerprint($fingerprint, $userdata))
			{
				# invalidate authcodedata so user has to use a new authcode again
				$authcodedata['valid'] = false;
				$authcodedata['validated'] = 12345;
			}
		}

		if($authcodeactive && !$authcodedata['valid'] )
		{
			# generate new authcode
			$authcodevalue 	= rand(10000, 99999);

			$mail 			= new SimpleMail($settings);

			$subject 		= Translations::translate('Your authentication code for Typemill');
			$message		= Translations::translate('Use the following authentication code to login into Typemill cms') . ': ' . $authcodevalue;

			$send 			= $mail->send($userdata['email'], $subject, $message);

			$send = true;

			if(!$send)
			{
				$title 		= Translations::translate('Error sending email');
				$message 	= Translations::translate('Dear ') . $userdata['username'] . ', ' . Translations::translate('we could not send the email with the authentication code to your address. Reason: ') . $mail->error;
			}
			else
			{
				# store authcode
				$user->setValue('authcodedata', $authcodevalue . ':' . time() . ':' . $authcodedata['validated']);
				$user->updateUser();
			}			

			# show authcode page
		    return $this->c->get('view')->render($response, 'auth/authcode.twig', [
				'username' => $userdata['username'],
		    ]);
		}

		# check if user has confirmed the account 
		if(isset($userdata['optintoken']) && $userdata['optintoken'])
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not confirmed yet.');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Your registration is not confirmed yet. Please check your e-mails and use the confirmation link.'));
		
			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$user->login();

#		return $response->withHeader('Location', $this->routeParser->urlFor('settings.show'))->withStatus(302);

		# if user is allowed to view content-area
		$acl = $this->c->get('acl');
		if($acl->hasRole($userdata['userrole']) && $acl->isAllowed($userdata['userrole'], 'content', 'view'))
		{
			$editor = (isset($this->settings['editor']) && $this->settings['editor'] == 'visual') ? 'visual' : 'raw';

			return $response->withHeader('Location', $this->routeParser->urlFor('content.' . $editor))->withStatus(302);
		}

		return $response->withHeader('Location', $this->routeParser->urlFor('user.account'))->withStatus(302);
	}


	# login user with valid authcode
	public function loginWithAuthcode(Request $request, Response $response)
	{
        $input 			= $request->getParsedBody();
		$validation		= new Validation();
		$securitylog 	= $this->settings['securitylog'] ?? false;

		if($validation->authcode($input) !== true)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: invalid authcode format');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Invalid authcode format, please try again.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}
		
		$user = new User();

		if(!$user->setUserWithPassword($input['username']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not found');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$userdata 		= $user->getUserData();
		$authcodevalue 	= $input['code-1'] . $input['code-2'] . $input['code-3'] . $input['code-4'] . $input['code-5'];
		$authcodedata 	= $this->checkAuthcode($userdata);

		if(!$this->validateAuthcode($authcodevalue, $authcodedata))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: authcode wrong or outdated.');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The authcode was wrong or outdated, please start again.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		# add the device fingerprint if not set yet
		$fingerprints = $userdata['fingerprints'] ?? [];
		$fingerprint = $this->generateDeviceFingerprint();
		if(!$this->findDeviceFingerprint($fingerprint, $fingerprints))
		{
			$fingerprints[] = $fingerprint;
			$user->setValue('fingerprints', $fingerprints);
		}

		# update authcode lastValidation and store
		$user->setValue('authcodedata', $authcodevalue . ':' . $authcodedata['generated'] . ':' . time());
		$user->updateUser();

		$user->login();

		# if user is allowed to view content-area
		$acl = $this->c->get('acl');
		if($acl->hasRole($userdata['userrole']) && $acl->isAllowed($userdata['userrole'], 'content', 'view'))
		{
			$editor = (isset($this->settings['editor']) && $this->settings['editor'] == 'visual') ? 'visual' : 'raw';

			return $response->withHeader('Location', $this->routeParser->urlFor('content.' . $editor))->withStatus(302);
		}

		return $response->withHeader('Location', $this->routeParser->urlFor('user.account'))->withStatus(302);
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


	# check if the stored authcode in userdata is valid and/or fresh
	private function checkAuthcode($userdata)
	{
		# format: 12345:time(generated):time(validated)

		$authcodedata = $userdata['authcodedata'] ?? false;

		if(!$authcodedata)
		{
			return $authcode = [
				'value' 		=> 12345,
				'generated'		=> 12345,
				'validated'		=> 12345,
				'valid'			=> false,
				'fresh'			=> false
			];
		}

		$validation 	= new Validation();
		$authcodedata 	= explode(":", $authcodedata);
		
		# validate format here, do we need it?

		$now 			= time();
		$lastValidation	= 60 * 60 * 24;
		$lastGeneration = 60 * 5;
		$valid 			= false;
		$fresh 			= false;

		# if last validation is less than 24 hours old
		if($now - $lastValidation < $authcodedata[2])
		{
			$valid = true;
		}

		# if last generation is less than 5 minutes old
		if($now - $lastGeneration < $authcodedata[1])
		{
			$fresh = true;
		}

		$authcode = [
			'value' 		=> $authcodedata[0],
			'generated'		=> $authcodedata[1],
			'validated'		=> $authcodedata[2],
			'valid'			=> $valid,
			'fresh'			=> $fresh
		];

		return $authcode;
	}

	# check if the submitted authcode is the same as the stored authcode 
	private function validateAuthcode($authcodevalue, $authcodedata)
	{
		if($authcodedata['valid'] === true)
		{
			return true;
		}

		if($authcodedata['fresh'] === false)
		{
			return false;
		}

		if($authcodevalue == $authcodedata['value'])
		{
			return true;
		}

		return false;
	}

	# create a simple device fingerprint
	private function generateDeviceFingerprint()
	{
		$userAgent 		= $_SERVER['HTTP_USER_AGENT'];
		$ipAddress 		= $_SERVER['REMOTE_ADDR'];
		$acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
	    
		$fingerprint 	= md5($userAgent . $ipAddress . $acceptLanguage);
	    
	    return $fingerprint;
	}

	# create a simple device fingerprint
	private function findDeviceFingerprint($fingerprint, $userdata)
	{
		if(!isset($userdata['fingerprints']) or empty($userdata['fingerprints']))
		{
			return false;
		}

		if(!in_array($fingerprint, $userdata['fingerprints']))
		{
			return false;
		}

		return true;
	}
}