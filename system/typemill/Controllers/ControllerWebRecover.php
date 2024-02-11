<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\User;
use Typemill\Models\Validation;
use Typemill\Models\SimpleMail;
use Typemill\Static\Translations;
use Typemill\Extensions\ParsedownExtension;

class ControllerWebRecover extends Controller
{
	public function showRecoverForm(Request $request, Response $response)
	{
	    return $this->c->get('view')->render($response, '/auth/recover.twig', [

	    ]);
	}
	
	public function recoverPassword(Request $request, Response $response)
	{
        $params 		= $request->getParsedBody();
		$settings 		= $this->c->get('settings');
		$urlinfo 		= $this->c->get('urlinfo');		

		if(!isset($params['email']) OR filter_var($params['email'], \FILTER_VALIDATE_EMAIL) === false )
		{
			$this->c->get('flash')->addMessage('error', Translations::translate('Please enter a valid email.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.recoverform'))->withStatus(302);
		}

		$title 			= Translations::translate('Check your inbox');
		$message 		= Translations::translate('Please check the inbox of your email account for more instructions.');

		$user 			= new User();
		$requiredUser 	= $user->findUsersByEmail($params['email']);

		if($requiredUser)
		{
			$user->setUserWithPassword($requiredUser[0]);

			$requiredUser 					= $user->getUserData();
			$recoverdate 					= date("Y-m-d H:i:s");
			$recovertoken 					= bin2hex(random_bytes(32));

			$url 	= $urlinfo['baseurl'] . '/tm/reset?username=' . $requiredUser['username'] . '&recovertoken=' . $recovertoken;
			$link 	= '<a href="'. $url . '">' . $url . '</a>';

			# define the headers
			$mail 		= new SimpleMail($settings);

			$subject 	= (isset($settings['recoversubject']) && ($settings['recoversubject'] != '') )  ? $settings['recoversubject'] : 'Recover your password';

			$messagetext	= Translations::translate('Dear user');
			$messagetext 	.= ",<br/><br/>";
			$messagetext	.= Translations::translate('please use the following link to set a new password') . ':';
			if(isset($settings['recovermessage']) && ($settings['recovermessage'] != ''))
			{
				$parsedown 		= new ParsedownExtension($urlinfo['baseurl']);
				$parsedown->setSafeMode(true);

				$contentArray 	= $parsedown->text($settings['recovermessage']);
				$messagetext	= $parsedown->markup($contentArray);
			}

			$message 		= $messagetext . "<br/><br/>" . $link;

			$send = $mail->send($requiredUser['email'], $subject, $message);

			if(!$send)
			{
				$title 		= Translations::translate('Error sending email');
				$message 	= Translations::translate('Dear ') . $requiredUser['username'] . ', ' . Translations::translate('we could not send the email with the password instructions to your address. Reason: ') . $mail->error;
			}
			else
			{
				# update user
				$user->setValue('recoverdate', $recoverdate);
				$user->setValue('recovertoken', $recovertoken);
				$user->updateUser();

				$title 		= Translations::translate('Check your inbox');
				$message 	= Translations::translate('Dear ') . $requiredUser['username'] . ', ' . Translations::translate('please check the inbox of your email account for more instructions.') . ' ' . Translations::translate('Do not forget to check your spam-folder if your inbox is empty.');
			}			
		}
		elseif(isset($settings['securitylog']) && $settings['securitylog'])
		{
			\Typemill\Static\Helpers::addLogEntry('wrong input for password recovery');
		}

	    return $this->c->get('view')->render($response, '/auth/recoverconf.twig', [
	    	'title' 	=> $title,
	    	'message'	=> $message
	    ]);
	}

	public function showPasswordResetForm(Request $request, Response $response, $args)
	{
		$params	 		= $request->getQueryParams();
		$securitylog  	= ( isset($settings['securitylog']) && $settings['securitylog'] ) ? true : false;

		if(!isset($params['username']) OR !isset($params['recovertoken']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('wrong password reset link');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('You tried to open the password reset page but the link was invalid.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);		
		}

		$user 			= new User();
		$requiredUser 	= $user->setUserWithPassword($params['username']);

		if(!$requiredUser)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('password reset link user not found');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('You tried to open the password reset page but the link was invalid.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$requiredUser = $user->getUserData();

		if(!isset($requiredUser['recovertoken']) OR $requiredUser['recovertoken'] != $params['recovertoken'] )
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('password reset link wrong token');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('You tried to open the password reset page but the link was invalid.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$recoverdate 	= isset($requiredUser['recoverdate']) ? $requiredUser['recoverdate'] : false;

		if(!$recoverdate)
		{
			$user->unsetValue('recovertoken');
			$user->updateUser();

			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('password reset link outdated');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The link to recover the password was too old. Please create a new one.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$now 			= new \DateTime('NOW');
		$recoverdate 	= new \DateTime($recoverdate);

		if(!$recoverdate)
		{
			$user->unsetValue('recovertoken');
			$user->unsetValue('recoverdate');
			$user->updateUser();

			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('password reset link wrong date format');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The link to recover the password was too old. Please create a new one.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

# here we should make the interval editable
		$validDate 		= $recoverdate->add(new \DateInterval('P1D'));

		if($validDate <= $now)
		{
			$user->unsetValue('recovertoken');
			$user->unsetValue('recoverdate');
			$user->updateUser();

			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('password reset link outdated');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The link to recover the password was too old. Please create a new one.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

	    return $this->c->get('view')->render($response, '/auth/reset.twig', [
			'recovertoken' 	=> $params['recovertoken'],
			'username' 		=> $requiredUser['username']
	    ]);
	}

	public function resetPassword(Request $request, Response $response, $args)
	{
        $params 		= $request->getParsedBody();
		$settings 		= $this->c->get('settings');
		$urlinfo 		= $this->c->get('urlinfo');

		if(!isset($params['username']) OR !isset($params['recovertoken']))
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password username or token missing');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('You tried to set a new password but username or token was invalid.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$validation		= new Validation();
		
		if($validation->recoverPassword($params) !== true)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password wrong input');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('Please correct your input.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.resetform', [], ['username' => $params['username'], 'recovertoken' => $params['recovertoken']]))->withStatus(302);
		}

		$user 			= new User();
		$requiredUser 	= $user->setUserWithPassword($params['username']);
		if(!$requiredUser)
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password user not found');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('You tried to open the password reset page but the link was invalid.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$requiredUser = $user->getUserData();

		if(!isset($requiredUser['recovertoken']) OR $requiredUser['recovertoken'] != $params['recovertoken'] )
		{
			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password wrong token');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('You tried to open the password reset page but the link was invalid.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$recoverdate 	= isset($requiredUser['recoverdate']) ? $requiredUser['recoverdate'] : false;

		if(!$recoverdate)
		{
			$user->unsetValue('recovertoken');
			$user->updateUser();

			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password date outdated');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The link to recover the password was too old. Please create a new one.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$now 			= new \DateTime('NOW');
		$recoverdate 	= new \DateTime($recoverdate);

		if(!$recoverdate)
		{
			$user->unsetValue('recovertoken');
			$user->unsetValue('recoverdate');
			$user->updateUser();

			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password wrong date format');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The link to recover the password was too old. Please create a new one.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

# here we should make the interval editable
		$validDate 		= $recoverdate->add(new \DateInterval('P1D'));

		if($validDate <= $now)
		{
			$user->unsetValue('recovertoken');
			$user->unsetValue('recoverdate');
			$user->updateUser();

			if($securitylog)
			{
				\Typemill\Static\Helpers::addLogEntry('create reset password outdated');
			}

			$this->c->get('flash')->addMessage('error', Translations::translate('The link to recover the password was too old. Please create a new one.'));

			return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
		}

		$user->unsetValue('recovertoken');
		$user->unsetValue('recoverdate');
		$password = $user->generatePassword($params['password']);
		$user->setValue('password', $password);
		$user->updateUser();

		unset($_SESSION['old']);

		$this->c->get('flash')->addMessage('info', Translations::translate('Please login with your new password.'));
		return $response->withHeader('Location', $this->routeParser->urlFor('auth.login'))->withStatus(302);
	}
}