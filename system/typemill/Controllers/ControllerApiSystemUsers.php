<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\User;

class ControllerApiSystemUsers extends Controller
{
	# getCurrentUser
	# getUserByName

	#returns userdata
	public function getUsersByNames(Request $request, Response $response, $args)
	{
		$usernames 		= $request->getQueryParams()['usernames'] ?? false;
		$user			= new User();
		$userdata 		= [];

		if($usernames)
		{
			foreach($usernames as $username)
			{
				$existinguser = $user->setUser($username);
				if($existinguser)
				{
					$userdata[] = $user->getUserData();
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

		$usernames 		= $user->findUsersByEmail($email);

		if($usernames)
		{
			foreach($usernames as $username)
			{
				$user->setUser($username);
				$userdata[] = $user->getUserData();
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

		$usernames 		= $user->findUsersByRole($role);

		if($usernames)
		{
			foreach($usernames as $username)
			{
				$user->setUser($username);
				$userdata[] = $user->getUserData();
			}
		}

		$response->getBody()->write(json_encode([
			'userdata' 	=> $userdata
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function updateUser(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$userdata 			= $params['userdata'] ?? false;
		$username 			= $params['userdata']['username'] ?? false;
		$isAdmin 			= $this->c->get('acl')->isAllowed($request->getAttribute('c_userrole'), 'user', 'write');

		$validate		= new Validation();

		# standard validation for new users
		$userroles 		= $this->c->get('acl')->getRoles();
		$valresult 		= $validate->existingUser($userdata, $userroles);
		if($valresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct the errors above.',
				'errors' 	=> $validate->returnFirstValidationErrors($valresult)
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# if a non-admin-user tries to update another account 
		if(!$isAdmin AND ($username !== $request->getAttribute('username')) )
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to update another user.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);			
		}

		# cleanup password entry
		if(isset($userdata['password']) AND $userdata['password'] == '')
		{
			unset($userdata['password']);
		}
		if(isset($userdata['newpassword']) AND $userdata['newpassword'] == '')
		{
			unset($userdata['newpassword']);
		}

		# validate passwort changes if valid input
		if(isset($userdata['password']) OR isset($userdata['newpassword']))
		{
			$validpass = $validate->newPassword($userdata);

			if($validpass === true)
			{
				# encrypt new password
				$userdata['password'] = $user->generatePassword($userdata['newpassword']);
			}
			elseif(is_array($validpass))
			{
				foreach($validpass as $fieldname => $errors)
				{
					$this->errors[$fieldname] = $errors[0];
				}
			}

			# in all cases unset newpassword
			unset($userdata['newpassword']);
		}

		# make sure you set a user with password when you update, otherwise it will delete the password completely
		$user 				= new User();
		$user->setUserWithPassword($username);
		$formdefinitions 	= $user->getUserFields($this->c->get('acl'), $request->getAttribute('c_userrole'));


		$validatedOutput 	= $validate->recursiveValidation($formdefinitions, $userdata);
		if(!empty($validate->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct tbe errors in form.',
				'errors' 	=> $validate->errors
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
			'message' => 'User has been updated.'
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function getNewUserForm(Request $request, Response $response, $args)
	{
		$userrole = $request->getQueryParams()['userrole'] ?? false;
		if(!$userrole)
		{
			$response->getBody()->write(json_encode([
				'message' => 'Userrole is required.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
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
				'message' => 'Userdata are required.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		$validate		= new Validation();

		# standard validation for new users
		$userroles 		= $this->c->get('acl')->getRoles();
		$valresult 		= $validate->newUser($userdata, $userroles);
		if($valresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct the errors above.',
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
				'message' 	=> 'Please correct tbe errors in form.',
				'errors' 	=> $validate->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if(!$user->createUser($validatedOutput))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'We could not store the new user',
				'error' 	=> $user->error,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

		$response->getBody()->write(json_encode([
			'message' 	=> 'New user created.',
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
				'message' => 'Username is required.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		# if a non-admin-user tries to delete another account 
		if(!$isAdmin AND ($username !== $request->getAttribute('c_username')) )
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to delete another user.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);			
		}

		$user = new User();
		if(!$user->setUser($username))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'We could not find the user',
				'error'		=> $user->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

		if(!$user->deleteUser())
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'We could not delete the user',
				'error'		=> $user->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);						
		}
/*
		# if user deleted his own account
		if(isset($_SESSION['user']) && $_SESSION['user'] == $username)
		{
			session_destroy();		
		}
*/
		$response->getBody()->write(json_encode([
			'message' 	=> 'User deleted.',
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}
}