<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Static\Translations;
use Typemill\Static\Session;

class ControllerApiSystemUsers extends Controller
{
	#returns userdata no in use???
	public function getUsersByNames(Request $request, Response $response, $args)
	{
		$usernames 		= $request->getQueryParams()['usernames'] ?? false;
		$user			= new User();
		$userdata 		= [];

		$validate		= new Validation();		

		if($usernames && is_array($usernames))
		{
			foreach($usernames as $username)
			{
				if($validate->username(['username' => $username]) === true)
				{
					$existinguser = $user->setUser($username);
					if($existinguser)
					{
						$userdata[] = $user->getUserData();
					}
				}
			}
		}

		$response->getBody()->write(json_encode([
			'userdata' => $userdata
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	# returns userdata
	public function getUsersByEmail(Request $request, Response $response, $args)
	{
		$email 			= $request->getQueryParams()['email'] ?? false;
		$user			= new User();
		$userdata 		= [];

		$validate		= new Validation();
		$valresult 		= $validate->emailsearch(['email' => $email]);

		if($valresult === true)
		{
			$usernames 		= $user->findUsersByEmail($email);

			if($usernames)
			{
				foreach($usernames as $username)
				{
					$user->setUser($username);
					$userdata[] = $user->getUserData();
				}
			}
		}

		$response->getBody()->write(json_encode([
			'userdata' 	=> $userdata
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	#returns userdata
	public function getUsersByRole(Request $request, Response $response, $args)
	{
		$role 			= $request->getQueryParams()['role'] ?? false;
		$user			= new User();
		$userdata 		= [];

		$userroles 		= $this->c->get('acl')->getRoles();

		if($role && in_array($role, $userroles))
		{
			$usernames 		= $user->findUsersByRole($role);

			if($usernames)
			{
				foreach($usernames as $username)
				{
					if($user->setUser($username))
					{
						$userdata[] = $user->getUserData();
					}
				}
			}
		}

		$response->getBody()->write(json_encode([
			'userdata' 	=> $userdata
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function updateUser(Request $request, Response $response, $args)
	{
		$params 		= $request->getParsedBody();
		$userdata 		= $params['userdata'] ?? false;
		$username 		= $params['userdata']['username'] ?? false;
		$isAdmin 		= $this->c->get('acl')->isAllowed($request->getAttribute('c_userrole'), 'user', 'update');

		if(!$userdata OR !$username)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Userdata or username is missing.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$validate		= new Validation();

		# standard validation for new users
		$userroles 		= $this->c->get('acl')->getRoles();
		$valresult 		= $validate->existingUser($userdata, $userroles);
		if($valresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validate->returnFirstValidationErrors($valresult)
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# if it is a non-admin-user
		if($isAdmin !== true)
		{
			# do not change userrole
			unset($userdata['userrole']);

			# if a non-admin-user tries to update another account 
			if(($username !== $request->getAttribute('c_username')))
			{
				$response->getBody()->write(json_encode([
					'message' => Translations::translate('You are not allowed to update another user.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
			}
		}

		# make sure you set a user with password when you update, otherwise it will delete the password completely
		$user 			= new User();
		$user->setUserWithPassword($username);

		# password validation
		$pwerrors 		= [];
		$oldpassword 	= ( isset($userdata['password']) AND ($userdata['password'] != '') ) ? $userdata['password'] : false;
		$newpassword 	= ( isset($userdata['newpassword']) AND ($userdata['newpassword'] != '') ) ? $userdata['newpassword'] : false;
		unset($userdata['password']);
		unset($userdata['newpassword']);

		if($isAdmin === true)
		{
			# admins can change passwords without old password
			if($newpassword)
			{
				$validpass = $validate->newPasswordAdmin(['newpassword' => $newpassword]);

				if($validpass === true)
				{
					# encrypt new password
					$userdata['password'] = $user->generatePassword($newpassword);
				}
				elseif(is_array($validpass))
				{
					foreach($validpass as $fieldname => $errors)
					{
						$pwerrors[$fieldname] = $errors[0];
					}
				}
			}
		}
		else
		{
			# non-admins can change password only with new and old password
			if($oldpassword OR $newpassword)
			{
				$validpass = $validate->newPassword(['password' => $oldpassword, 'newpassword' => $newpassword]);

				if(is_array($validpass))
				{
					foreach($validpass as $fieldname => $errors)
					{
						$pwerrors[$fieldname] = $errors[0];
					}
				}
				elseif(!password_verify($oldpassword, $user->getValue('password')))
				{
					$pwerrors['password'] = 'Old password is wrong.';					
				}
				elseif($validpass === true)
				{
					# encrypt new password
					$userdata['password'] = $user->generatePassword($newpassword);
				}
			}
		}

		if(!empty($pwerrors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $pwerrors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# check if loginlink is activated
		$loginlink 			= false;
		if($userdata['userrole'] == 'member' && isset($this->settings['loginlink']) && $this->settings['loginlink'])
		{
			$loginlink 		= true;
		}

		# we have to validate again because of additional dynamic fields
		$formdefinitions 	= $user->getUserFields($this->c->get('acl'), $request->getAttribute('c_userrole'), NULL, $loginlink);
		$validatedOutput 	= $validate->recursiveValidation($formdefinitions, $userdata);
		if(!empty($validate->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validateErrors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# if input is valid, overwrite value in original user
		foreach($validatedOutput as $fieldname => $value)
		{
			$user->setValue($fieldname, $value);			
		}

		if(!$user->updateUser())
		{
			$response->getBody()->write(json_encode([
				'message' => $user->getError()
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		$response->getBody()->write(json_encode([
			'message' => Translations::translate('User has been updated.')
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function getNewUserForm(Request $request, Response $response, $args)
	{
		$userrole = $request->getQueryParams()['userrole'] ?? false;
		if(!$userrole)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Userrole is required.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$user 		= new User();
		$userform 	= $user->getUserFields($this->c->get('acl'), $userrole,$inspectorrole = $request->getAttribute('c_userrole'));

		# fix the standard form
		$userform['password']['label'] = 'Password';
		$userform['password']['generator'] = true;
		$userform['username']['label'] = 'Username';
		unset($userform['username']['readonly']);
		unset($userform['userrole']);
		unset($userform['newpassword']);

		$response->getBody()->write(json_encode([
			'userform' => $userform,
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function createUser(Request $request, Response $response, $args)
	{
		$params 		= $request->getParsedBody();
		$userdata 		= $params['userdata'] ?? false;
		if(!$userdata)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Userdata are required.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$validate		= new Validation();

		# standard validation for new users
		$userroles 		= $this->c->get('acl')->getRoles();
		$valresult 		= $validate->newUser($userdata, $userroles);
		if($valresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validate->returnFirstValidationErrors($valresult)
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}


		# additional validation for extra fields and image handling
		$user 				= new User();
		$formdefinitions 	= $user->getUserFields($this->c->get('acl'), $userdata['userrole'],$inspectorrole = $request->getAttribute('c_userrole'));
		unset($formdefinitions['username']['readonly']);
		$validatedOutput = $validate->recursiveValidation($formdefinitions, $userdata);
		if(!empty($validate->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validate->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if(!$user->createUser($validatedOutput))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We could not store the new user'),
				'error' 	=> $user->error,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		$response->getBody()->write(json_encode([
			'message' 	=> Translations::translate('New user created.'),
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}


	public function deleteUser(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$username 			= $params['username'] ?? false;
		$isAdmin 			= $this->c->get('acl')->isAllowed($request->getAttribute('c_userrole'), 'user', 'delete');

		if(!$username)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Username is required.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# if a non-admin-user tries to delete another account 
		if(!$isAdmin AND ($username !== $request->getAttribute('c_username')) )
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('You are not allowed to delete another user.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);			
		}

		$user = new User();
		if(!$user->setUser($username))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We could not find the user'),
				'error'		=> $user->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);			
		}

		if(!$user->deleteUser())
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We could not delete the user'),
				'error'		=> $user->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);						
		}

		$logout = false;
		# if user deleted his own account
		if($username == $request->getAttribute('c_username'))
		{
			$logout = true;
			Session::stopSession();
		}

		$response->getBody()->write(json_encode([
			'message' 	=> Translations::translate('User deleted.'),
			'logout' 	=> $logout
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}
}