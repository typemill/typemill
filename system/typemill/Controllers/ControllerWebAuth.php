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
		$authcodeactive = $this->isAuthcodeActive($this->settings);
		$authtitle 		= Translations::translate('Verification code missing?');
		$authtext 		= Translations::translate('If you did not receive an email with the verification code, then the username or password you entered was wrong. Please try again.');

		if($validation->signin($input) !== true)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: invalid data');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}
		
		$user = new User();

		if(!$user->setUserWithPassword($input['username']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not found');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

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
					'authtitle' 	=> $authtitle,
					'authtext' 		=> $authtext
			    ]);
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

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

			$mail 			= new SimpleMail($this->settings);

			$subject 		= Translations::translate('Your Typemill verification code');
			$message		= Translations::translate('Dear user') . ',<br><br>';
			$message		.= Translations::translate('Someone tried to log in to your Typemill website and we want to make sure it is you. Enter the following verification code to finish your login. The code will be valid for 5 minutes.');
			$message 		.= '<br><br>' . $authcodevalue . '<br><br>';
			$message		.= Translations::translate('If you did not make this login attempt, please reset your password immediately.');

			$send 			= $mail->send($userdata['email'], $subject, $message);

			if(!$send)
			{
				$authtitle 		= Translations::translate('Error sending email');
				$authtext 		= Translations::translate('We could not send the email with the verification code to your address. Reason: ') . $mail->error;
			}
			else
			{
				# store authcode
				$user->setValue('authcodedata', $authcodevalue . ':' . time() . ':' . $authcodedata['validated']);
				$user->updateUser();
			}			

			# show authcode page
		    return $this->c->get('view')->render($response, 'auth/authcode.twig', [
				'username' 		=> $userdata['username'],
				'authtitle' 	=> $authtitle,
				'authtext'  	=> $authtext
		    ]);
		}

		# check if user has confirmed the account 
		if(isset($userdata['optintoken']) && $userdata['optintoken'])
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not confirmed yet.');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Your registration is not confirmed yet. Please check your e-mails and use the confirmation link.'));
			}
		
			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$user->login();

		$redirect = $this->getRedirectDestination($userdata['userrole']);

		return $response->withHeader('Location', $this->routeParser->urlFor($redirect))->withStatus(302);
	}

	# login a user with valid authcode
	public function loginWithAuthcode(Request $request, Response $response)
	{
        $input 			= $request->getParsedBody();
		$validation		= new Validation();
		$securitylog 	= $this->settings['securitylog'] ?? false;

		if($validation->authcode($input) !== true)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: invalid verification code format');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Invalid verification code format, please try again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}
		
		$user = new User();

		if(!$user->setUserWithPassword($input['username']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not found');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$userdata 		= $user->getUserData();
		$authcodevalue 	= $input['code-1'] . $input['code-2'] . $input['code-3'] . $input['code-4'] . $input['code-5'];
		$authcodedata 	= $this->checkAuthcode($userdata);

		if(!$this->validateAuthcode($authcodevalue, $authcodedata))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: verification code wrong or outdated.');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('The verification was wrong or outdated, please start again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		# add the device fingerprint if not set yet
		$fingerprints 	= $userdata['fingerprints'] ?? [];
		$fingerprint 	= $this->generateDeviceFingerprint();
		if(!$this->findDeviceFingerprint($fingerprint, $userdata))
		{
			$fingerprints[] = $fingerprint;
			$user->setValue('fingerprints', $fingerprints);
		}

		# update authcode lastValidation and store
		$user->setValue('authcodedata', $authcodevalue . ':' . $authcodedata['generated'] . ':' . time());
		$user->updateUser();

		$user->login();

		$redirect = $this->getRedirectDestination($userdata['userrole']);

		return $response->withHeader('Location', $this->routeParser->urlFor($redirect))->withStatus(302);
	}

	public function loginlink(Request $request, Response $response, $args)
	{
		if(!isset($this->settings['loginlink']) OR !$this->settings['loginlink'])
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: not activated');
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		# optionally check trusted ips
		$trustedLogin	= ( isset($this->settings['trustedloginreferrer']) && !empty($this->settings['trustedloginreferrer']) ) ? explode(",", $this->settings['trustedloginreferrer']) : [];
		$ipAddress 		= $_SERVER['REMOTE_ADDR'] ?? null;
		if (
			!empty($trustedLogin)
			&& !in_array($ipAddress, $trustedLogin)
		)
		{            
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: remote address is not a trusted ip');
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

        $input 			= $request->getQueryParams();
		$validation		= new Validation();
		$securitylog 	= $this->settings['securitylog'] ?? false;

		if($validation->signin($input) !== true)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: invalid data');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}
		
		$user = new User();

		if(!$user->setUserWithPassword($input['username']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: user not found');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$userdata 		= $user->getUserData();

		if($userdata['userrole'] != 'guest')
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: user has not a member role. Only members can use loginlinks.');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('User is not a member.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);			
		}

		if(!isset($userdata['linkaccess']) OR ($userdata['linkaccess'] !== true))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: loginlink for user ' . $userdata['username'] . ' is not activated.');
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);			
		}

		if($userdata && !password_verify($input['password'], $userdata['password']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: wrong password');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Wrong password or username, please try again.'));
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		# check if user has confirmed the account 
		if(isset($userdata['optintoken']) && $userdata['optintoken'])
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: user not confirmed yet.');
			}

			if($this->c->get('flash'))
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('Your registration is not confirmed yet. Please check your e-mails and use the confirmation link.'));
			}
		
			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$user->login();

		$redirect = $this->getRedirectDestination($userdata['userrole']);

		return $response->withHeader('Location', $this->routeParser->urlFor($redirect))->withStatus(302);
	}


	private function getRedirectDestination(string $userrole)
	{
		# decide where to redirect after login, configurable in settings -> system.yaml
		$redirect 	= 'home';
		$acl 		= $this->c->get('acl');
		if($acl->hasRole($userrole))
		{
			if($acl->isAllowed($userrole, 'system', 'read'))
			{
				# defaults to content editor
				$redirect = 'content';
				if(isset($this->settings['redirectadminrights']) && $this->settings['redirectadminrights'])
				{
					$redirect = $this->settings['redirectadminrights'];
				}
			}
			elseif($acl->isAllowed($userrole, 'content', 'read'))
			{
				# defaults to content editor
				$redirect = 'content';
				if(isset($this->settings['redirectcontentrights']) && $this->settings['redirectcontentrights'])
				{
					$redirect = $this->settings['redirectcontentrights'];
				}
			}
			elseif($acl->isAllowed($userrole, 'account', 'read'))
			{
				$redirect = 'user.account';
				if(isset($this->settings['redirectaccountrights']) && $this->settings['redirectaccountrights'])
				{
					$redirect = $this->settings['redirectaccountrights'];
				}
			}

			if($redirect == 'content')
			{
				$editor = (isset($this->settings['editor']) && $this->settings['editor'] == 'visual') ? 'visual' : 'raw';
				$redirect = 'content.' . $editor;
			}
		}

		return $redirect;
	}

	private function isAuthcodeActive($settings)
	{
		if(
			isset($settings['authcode']) &&
			$settings['authcode'] &&
			isset($settings['mailfrom']) &&
			filter_var($settings['mailfrom'], FILTER_VALIDATE_EMAIL)
		)
		{
			return true;
		}

		return false;
	}

	# log out a user
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