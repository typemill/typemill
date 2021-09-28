<?php

namespace Typemill\Controllers;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\WriteYaml;
use Typemill\Extensions\ParsedownExtension;

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
		$settings = $this->c->get('settings');

		return $this->render($response, '/auth/login.twig', ['settings' => $settings]);
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

	public function showRecoverPassword(Request $request, Response $response, $args)
	{
		$data 			= array();
		
		return $this->render($response, '/auth/recoverpw.twig', $data);
	}

	public function recoverPassword(Request $request, Response $response, $args)
	{
		$params	 		= $request->getParams();
		$validation		= new Validation();
		$settings 		= $this->c->get('settings');
		$uri 			= $request->getUri()->withUserInfo('');
		$base_url		= $uri->getBaseUrl();

		if(!isset($params['email']) OR filter_var($params['email'], \FILTER_VALIDATE_EMAIL) === false )
		{
			$this->c->flash->addMessage('error', 'Please enter a valid email.');
			return $response->withRedirect($this->c->router->pathFor('auth.recoverpwshow'));
		}

		$user = new User();
		$requiredUser = $user->findUsersByEmail($params['email']);

		if(!$requiredUser)
		{
			$this->c->flash->addMessage('error', 'The email address is unknown.');
			return $response->withRedirect($this->c->router->pathFor('auth.recoverpwshow'));			
		}

		$requiredUser = $user->getSecureUser($requiredUser[0]);
		
		$requiredUser['recoverdate'] 	= date("Y-m-d H:i:s");
		$requiredUser['recovertoken'] 	= bin2hex(random_bytes(32));

		$url 	= $base_url . '/tm/recoverpwnew?username=' . $requiredUser['username'] . '&recovertoken=' . $requiredUser['recovertoken'];
		$link 	= '<a href="'. $url . '">' . $url . '</a>';

		# define the headers
		$headers 	= 'Content-Type: text/html; charset=utf-8' . "\r\n";
		$headers 	.= 'Content-Transfer-Encoding: base64' . "\r\n";
		if(isset($settings['recoverfrom']) && $settings['recoverfrom'] != '')
		{
			$headers 	.= 'From: ' . $settings['recoverfrom'];
		}

		$subjectline 	= (isset($settings['recoversubject']) && ($settings['recoversubject'] != '') )  ? $settings['recoversubject'] : 'Recover your password';
		$subject 		= '=?UTF-8?B?' . base64_encode($subjectline) . '?=';

		$messagetext	= "Dear user,<br/><br/>please use the following link to set a new password:";
		if(isset($settings['recovermessage']) && ($settings['recovermessage'] != ''))
		{
			$parsedown 		= new ParsedownExtension($base_url);
			$parsedown->setSafeMode(true);

			$contentArray 	= $parsedown->text($settings['recovermessage']);
			$messagetext	= $parsedown->markup($contentArray);
		}

		$message 		= base64_encode($messagetext . "<br/><br/>" . $link);

		# store user
		$user->updateUser($requiredUser);

		$send = mail($requiredUser['email'], $subject, $message, $headers);

		if(!$send)
		{
			$data = [
				'title' => 'We could not send the email',
				'message' => 'Dear ' . $requiredUser['username'] . ', we could not send the email with the password instructions to your address. You can try it again but chances are low that it will work next time. Please contact the website owner and ask for help.',
			];
		}
		else
		{
			# store user
			$user->updateUser($requiredUser);

			$data = [
				'title' => 'Please check your inbox',
				'message' => 'Dear ' . $requiredUser['username'] . ', please check the inbox of your email account. We have sent you some short instructions how to recover your password. Do not forget to check your spam-folder if your inbox is empty.',
			];
		}
		
		return $this->render($response, '/auth/recoverpwsend.twig', $data);
	}

	public function showRecoverPasswordNew(Request $request, Response $response, $args)
	{
		$params	 		= $request->getParams();

		if(!isset($params['username']) OR !isset($params['recovertoken']))
		{
			$this->c->flash->addMessage('error', 'Ups, you tried to open the password recovery page but the link was invalid.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$user = new user();

		$requiredUser = $user->getSecureUser($params['username']);

		if(!$requiredUser)
		{
			$this->c->flash->addMessage('error', 'Ups, you tried to open the password recovery page but the link was invalid.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));	
		}

		if(!isset($requiredUser['recovertoken']) OR $requiredUser['recovertoken'] != $params['recovertoken'] )
		{
			$this->c->flash->addMessage('error', 'Ups, you tried to open the password recovery page but the link was invalid.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$recoverdate 	= isset($requiredUser['recoverdate']) ? $requiredUser['recoverdate'] : false;

		if(!$recoverdate )
		{
			$user->unsetFromUser($requiredUser['username'], ['recovertoken']);

			$this->c->flash->addMessage('error', 'The link to recover the password was too old. Please create a new one.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$now 			= new \DateTime('NOW');
		$recoverdate 	= new \DateTime($recoverdate);

		if(!$recoverdate)
		{
			$user->unsetFromUser($requiredUser['username'], ['recovertoken', 'recoverdate']);

			$this->c->flash->addMessage('error', 'The link to recover the password was too old. Please create a new one.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$validDate 		= $recoverdate->add(new \DateInterval('P1D'));

		if($validDate <= $now)
		{
			$user->unsetFromUser($requiredUser['username'], ['recovertoken', 'recoverdate']);

			$this->c->flash->addMessage('error', 'The link to recover the password was too old. Please create a new one.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));			
		}

		return $this->render($response, '/auth/recoverpwnew.twig', ['recovertoken' => $params['recovertoken'],'username' => $requiredUser['username']]);
	}

	public function createRecoverPasswordNew(Request $request, Response $response, $args)
	{
		$params	 		= $request->getParams();

		if(!isset($params['username']) OR !isset($params['recovertoken']))
		{
			$this->c->flash->addMessage('error', 'Ups, you tried to set a new password but username or token was invalid.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$validation		= new Validation();
		
		if(!$validation->recoverPassword($params))
		{
			$this->c->flash->addMessage('error', 'Please check your input.');
			return $response->withRedirect($this->c->router->pathFor('auth.recoverpwshownew',[], ['username' => $params['username'], 'recovertoken' => $params['recovertoken']]));
		}

		$user = new user();

		$requiredUser = $user->getSecureUser($params['username']);

		if(!$requiredUser)
		{
			$this->c->flash->addMessage('error', 'Ups, you tried to create a new password but the username was invalid.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));	
		}

		if(!isset($requiredUser['recovertoken']) OR $requiredUser['recovertoken'] != $params['recovertoken'] )
		{
			$this->c->flash->addMessage('error', 'Ups, you tried to create a new password but the token was invalid.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$recoverdate 	= isset($requiredUser['recoverdate']) ? $requiredUser['recoverdate'] : false;

		if(!$recoverdate )
		{
			$user->unsetFromUser($requiredUser['username'], ['recovertoken']);

			$this->c->flash->addMessage('error', 'The date for the password reset was invalid. Please create a new one.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$now 			= new \DateTime('NOW');
		$recoverdate 	= new \DateTime($recoverdate);

		if(!$recoverdate)
		{
			$user->unsetFromUser($requiredUser['username'], ['recovertoken', 'recoverdate']);

			$this->c->flash->addMessage('error', 'The date for the password reset was too old. Please create a new one.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$validDate 		= $recoverdate->add(new \DateInterval('P1D'));

		if($validDate <= $now)
		{
			$user->unsetFromUser($requiredUser['username'], ['recovertoken', 'recoverdate']);

			$this->c->flash->addMessage('error', 'The link to recover the password was too old. Please create a new one.');
			return $response->withRedirect($this->c->router->pathFor('auth.show'));
		}

		$requiredUser['password'] = $params['password'];
		$user->updateUser($requiredUser);
		$user->unsetFromUser($requiredUser['username'], ['recovertoken', 'recoverdate']);

		unset($_SESSION['old']);

		$this->c->flash->addMessage('info', 'A new password has been created. Please login.');
		return $response->withRedirect($this->c->router->pathFor('auth.show'));
	}
}