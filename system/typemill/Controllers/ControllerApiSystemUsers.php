<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Yaml;
use Typemill\Models\User;

class ControllerApiSystemUsers extends ControllerData
{

	# getCurrentUser
	# getUserByName

	#returns userdata
	public function getUsersByNames($request, $response, $args)
	{
		# minimum permission are admin rights
		if(!$this->c->get('acl')->isAllowed($request->getAttribute('userrole'), 'system', 'update'))
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to update settings.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
		}

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
	public function getUsersByEmail($request, $response, $args)
	{
		# minimum permission are admin rights
		if(!$this->c->get('acl')->isAllowed($request->getAttribute('userrole'), 'system', 'update'))
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to update settings.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
		}

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
	public function getUsersByRole($request, $response, $args)
	{
		# minimum permission are admin rights
		if(!$this->c->get('acl')->isAllowed($request->getAttribute('userrole'), 'system', 'update'))
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to update settings.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
		}

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

	public function updateUser($request, $response, $args)
	{
		$params 			= $request->getParsedBody();
		$userdata 			= $params['userdata'] ?? false;
		$username 			= $params['userdata']['username'] ?? false;
		$isAdmin 			= $this->c->get('acl')->isAllowed($request->getAttribute('userrole'), 'userlist', 'write');

		if(!$userdata OR !$username)
		{
			$response->getBody()->write(json_encode([
				'message' => 'Userdata and username is required.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		# if a non-admin-user tries to update another account 
		if(!$isAdmin AND ($username !== $request->getAttribute('username')) )
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to update another user.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);			
		}

		# make sure that invalid password input is stripped out
		if(isset($userdata['password']) && $userdata['password'] == '' )
		{
			unset($userdata['password']);
			unset($userdata['newpassword']);
		}

		$user 				= new User();

		# make sure you set a user with password when you update, otherwise it will delete the password completely
		$user->setUserWithPassword($username);

		$userfields 		= $this->getUserFields($request->getAttribute('userrole'));

		# validate input
		$validator 			= new Validation();

		# loop through form-definitions, ignores everything that is not defined in yaml
		foreach($userfields as $fieldname => $fielddefinitions)
		{
			# if there is no value for a field
			if(!isset($userdata[$fieldname]))
			{
				continue;
			}

			# ignore readonly-fields
			if(isset($fielddefinitions['readonly']) && ($fielddefinitions['readonly'] !== false) )
			{
				continue;
			}

			# new password needs special validation
			if($fieldname == 'password')
			{
				$validationresult = $validator->newPassword($userdata);

				if($validationresult === true)
				{
					# encrypt new password
					$newpassword = $user->generatePassword($userdata['newpassword']);

					# if input is valid, overwrite value in original user
					$user->setValue('password', $newpassword);
				}
				else
				{
					$this->errors[$fieldname] = $validationresult[$fieldname][0];
				}
			}
			else
			{
				# standard validation
				$validationresult = $validator->field($fieldname, $userdata[$fieldname], $fielddefinitions);

				if($validationresult === true)
				{
					# if input is valid, overwrite value in original user
					$user->setValue($fieldname, $userdata[$fieldname]);
				}
				else
				{
					$this->errors[$fieldname] = $validationresult[$fieldname][0];
				}
			}
		}

		if(!empty($this->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct tbe errors in form.',
				'errors' 	=> $this->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
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

/*
	public function updateUser($request, $response, $args)
	{
		# check if user is allowed to view (edit) userlist and other users
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'userlist', 'write'))
		{
			# if an editor tries to update other userdata than its own 
			if($_SESSION['user'] !== $userdata['username'])
			{
				return $response->withRedirect($this->c->router->pathFor('user.account'));
			}
			
			# non admins cannot change their userrole, so set it to session-value
			$userdata['userrole'] = $_SESSION['role'];
		}



			$params 		= $request->getParams();
			$userdata 		= $params['user'];
			$user 			= new User();
			$validate		= new Validation();
			$userroles 		= $this->c->acl->getRoles();

			$redirectRoute	= ($userdata['username'] == $_SESSION['user']) ? $this->c->router->pathFor('user.account') : $this->c->router->pathFor('user.show', ['username' => $userdata['username']]);

			# validate standard fields for users
			if($validate->existingUser($userdata, $userroles))
			{
				# validate custom input fields and return images
				$userfields = $this->getUserFields($userdata['userrole']);
				$imageFields = $this->validateInput('users', 'user', $userdata, $validate, $userfields);

				if(!empty($imageFields))
				{
					$images = $request->getUploadedFiles();

					if(isset($images['user']))
					{
						# set image size
						$settings = $this->c->get('settings');
						$imageSizes = $settings['images'];
						$imageSizes['live'] = ['width' => 500, 'height' => 500];
						$settings->replace(['images' => $imageSizes]);
						$imageresult = $this->saveImages($imageFields, $userdata, $settings, $images['user']);
		
						if(isset($_SESSION['slimFlash']['error']))
						{
							return $response->withRedirect($redirectRoute);
						}
						elseif(isset($imageresult['username']))
						{
							$userdata = $imageresult;
						}
					}
				}

				# check for errors and redirect to path, if errors found 
				if(isset($_SESSION['errors']))
				{
					$this->c->flash->addMessage('error', 'Please correct the errors');
					return $response->withRedirect($redirectRoute);
				}

				if(empty($userdata['password']) AND empty($userdata['newpassword']))
				{
					# make sure no invalid passwords go into model
					unset($userdata['password']);
					unset($userdata['newpassword']);

					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($redirectRoute);
				}
				elseif($validate->newPassword($userdata))
				{
					$userdata['password'] = $userdata['newpassword'];
					unset($userdata['newpassword']);

					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($redirectRoute);
				}
			}

			# change error-array for formbuilder
			$errors = $_SESSION['errors'];
			unset($_SESSION['errors']);
			$_SESSION['errors']['user'] = $errors;#

			$this->c->flash->addMessage('error', 'Please correct your input');
			return $response->withRedirect($redirectRoute);
		}
	}
	*/	
}