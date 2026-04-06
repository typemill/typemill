<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\Mail;
use Typemill\Static\Translations;
use Typemill\Events\OnUserAuthenticate;


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

		# Use plugins like ldap for authentication
		$authResult = $this->c->get('dispatcher')->dispatch(new OnUserAuthenticate($input), 'OnUserAuthenticate')->getData();
		if(isset($authResult['authenticated']) && $authResult['authenticated'] === true)
		{
		    $user = new User();

		    # ensure user exists (plugin may have created it)
		    if($user->setUser($authResult['username']))
		    {
		        $userdata = $user->getUserData();

		        if($this->showAuthcodePage($user, $userdata))
		        {
					# show authcode page
				    return $this->c->get('view')->render($response, 'auth/authcode.twig', [
						'username' 		=> $userdata['username'],
						'authtitle' 	=> $authtitle,
						'authtext' 		=> $authtext
				    ]);
		        }

		        $user->login();

		        $redirect = $this->getRedirectDestination($userdata['userrole']);
		        return $response->withHeader('Location', $this->routeParser->urlFor($redirect))->withStatus(302);
		    }
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
		if($userdata && !password_verify($input['password'], $userdata['password']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('login: wrong password');
			}

			# always show authcode page, so attacker does not know if email or password was wrong or mail was send.
	        if($this->showAuthcodePage($user, $userdata))
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

		if($this->showAuthcodePage($user, $userdata))
		{
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

		if(isset($userdata['optintoken']) && $userdata['optintoken'])
		{
		    if($securitylog)
		    {
		        \Typemill\Static\Helpers::addLogEntry('login: user not confirmed yet.');
		    }

		    if($this->c->get('flash'))
		    {
		        $this->c->get('flash')->addMessage('error', Translations::translate('Your registration is not confirmed yet.'));
		    }

		    return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		$authcodevalue 	= $input['code-1'] . $input['code-2'] . $input['code-3'] . $input['code-4'] . $input['code-5'];
		$validAuthData 	= $this->validateAuthcode($userdata, $authcodevalue);

		if(!$validAuthData)
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

		# update authcode lastValidation and store
		$user->setValue('authcodedata', $validAuthData);		

		# add the device fingerprint if not set yet
		$fingerprints 	= $userdata['fingerprints'] ?? [];
		$fingerprint 	= $this->generateDeviceFingerprint();
		if(!in_array($fingerprint, $fingerprints))
		{
			$fingerprints[] = $fingerprint;
			$user->setValue('fingerprints', $fingerprints);
		}

		# update userdata
		$user->updateUser();

		$user->login();

		$redirect = $this->getRedirectDestination($userdata['userrole']);

		return $response->withHeader('Location', $this->routeParser->urlFor($redirect))->withStatus(302);
	}

	public function loginlink(Request $request, Response $response, $args)
	{
        $input 			= $request->getQueryParams();
		$validation		= new Validation();
		$securitylog 	= $this->settings['securitylog'] ?? false;

		if(!isset($this->settings['loginlink']) OR !$this->settings['loginlink'])
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('loginlink: not activated');
			}

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
		}

		# optionally check trusted ips and hosts
		$trustedReferrers = $this->settings['trustedloginreferrer'] ?? false;
		if($trustedReferrers && is_string($trustedReferrers) && $trustedReferrers !== '')
		{
		    $trustedLogin 	= array_filter(array_map('trim', explode(',', $trustedReferrers)), 'strlen');
			if (
				empty($trustedLogin)
			)
			{
				if($securitylog)
				{
					\Typemill\Static\Helpers::addLogEntry('loginlink: input in trusted referrers not valid');
				}

				return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
			}

		    $ipAddress  	= $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
		    $referrer   	= $_SERVER['HTTP_REFERER'] ?? null;
			if(!$ipAddress && !$referrer)
			{
				if($securitylog)
				{
					\Typemill\Static\Helpers::addLogEntry('loginlink: we could not identify HTTP_X_FORWARDED_FOR, REMOTE_ADDR, OR HTTP_REFERER.');
				}

				return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);				
			}

			if (
		        !in_array($ipAddress, $trustedLogin)
		        && !in_array(parse_url($referrer, PHP_URL_HOST), $trustedLogin)
			)
			{
				if($securitylog)
				{
					\Typemill\Static\Helpers::addLogEntry('loginlink: remote address is not a trusted ip or host');
				}

				return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
			}
		}

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

	# log out a user
	public function logout(Request $request, Response $response)
	{
		\Typemill\Static\Session::stopSession();

		return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
	}


	#############
	# AUTHCODE  #
	#############

	# format is 'value:timeGenerated:timeLastValidated'
    # authcodedata: '38251:1705000992:1705001041'
    # fingerprints:
    # - 0f5113d4a2b4751f15a2ed787145978e
    # - 6968aacfd1b6ccd3f9faa5db8260ebc5

	# check if user should see authcode page
	private function showAuthcodePage($user, $userdata)
	{
		if(!$this->isAuthcodeActive($this->settings))
		{
			# do not show authcode screen
			return false;
		}

		# get device fingerprint because authcode is only valid for a specific device
		$fingerprint = $this->generateDeviceFingerprint();

		# if authcode is not valid or device not registered yet
		if(
			!$this->hasValidAuthCode($userdata)
			OR
			!$this->findDeviceFingerprint($fingerprint, $userdata)
		)
		{
			# generate new authcode
			$authcodevalue 	= $this->generateAuthcodeValue();

			# update authcode and fingerprint in userdata
			$this->storeNewAuthcode($authcodevalue, $fingerprint, $user);
			
			# send mail
			$this->sendAuthcodeToUser($authcodevalue, $userdata);

			# show authcode screen
			return true;
		}

		# if authcode and fingerprint are valid, dont show authcode screen
		return false;
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

	private function validateAuthcode($userdata, $authcode)
	{
	    $authcodedata = $userdata['authcodedata'] ?? false;

	    if(!$authcodedata)
	    {
	        return false;
	    }

		$authcodedata 	= explode(":", $authcodedata);
	    $aValue     	= $authcodedata[0] ?? false;
	    $aGenerated 	= $authcodedata[1] ?? false;
	    $aValidated 	= $authcodedata[2] ?? false;

		if(!ctype_digit($aValue) || !ctype_digit($aGenerated) || !ctype_digit($aValidated))
		{
		    return false;
		}

	    $now = time();

	    # already used → reject
	    if($aValidated > 0)
	    {
	        return false;
	    }

	    # expired (older than 5 minutes) → reject
	    if($now - (60 * 5) > $aGenerated)
	    {
	        return false;
	    }

	    # wrong code → reject
	    if($aValue !== $authcode)
	    {
	        return false;
	    }

	    # mark as used (only update validated timestamp)
	    return $aValue . ':' . $aGenerated . ':' . $now;
	}

	private function hasValidAuthcode($userdata)
	{
		$authcodedata = $userdata['authcodedata'] ?? false;

		# user has no stored authcode yet
		if(!$authcodedata)
		{
			return false;
		}

		$authcodedata 	= explode(":", $authcodedata);
	    $aValue     	= $authcodedata[0] ?? false;
	    $aGenerated 	= $authcodedata[1] ?? false;
	    $aValidated 	= $authcodedata[2] ?? false;

		if(!ctype_digit($aValue) || !ctype_digit($aGenerated) || !ctype_digit($aValidated))
		{
		    return false;
		}

		# check if last validation is older than 24 hours
		$now 			= time();
		$lastValidation	= 60 * 60 * 24;
		if($now - $lastValidation > $aValidated)
		{
			return false;
		}
		
		return true;
	}

	private function generateAuthcodeValue()
	{
		return rand(10000, 99999);
	}

	private function storeNewAuthcode($authcodevalue, $fingerprint, $user)
	{
	    $userdata 		= $user->getUserData();
	    $fingerprints 	= $userdata['fingerprints'] ?? [];
		if(!in_array($fingerprint, $fingerprints))
		{
		    $fingerprints[] = $fingerprint;
		}

	    $user->setValue('fingerprints', $fingerprints);

		$generated 		= time();
		$lastValidated 	= 0; # not validated yet

		$user->setValue(
			'authcodedata', 
			$authcodevalue . 
			':' . $generated . 
			':' . $lastValidated
		);

		$user->updateUser();
	}

	private function sendAuthcodeToUser($authcodevalue, $userdata)
	{
		$mail 			= new Mail($this->settings);

		$subject 		= Translations::translate('Your Typemill verification code');

		$message		= Translations::translate('Dear user') . ',<br><br>';
		$message		.= Translations::translate('Someone tried to log in to your Typemill website and we want to make sure it is you. Enter the following verification code to finish your login. The code will be valid for 5 minutes.');
		$message 		.= '<br><br>' . $authcodevalue . '<br><br>';
		$message		.= Translations::translate('If you did not make this login attempt, please reset your password immediately.');

		$send 			= $mail->send($userdata['email'], $subject, $message);

		if(!$send)
		{
			return false;
			
			$authtitle 		= Translations::translate('Error sending email');
			$authtext 		= Translations::translate('We could not send the email with the verification code to your address. Reason: ') . $mail->error;
		}

		return true;
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

	# search for fingerprints
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