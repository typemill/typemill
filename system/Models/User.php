<?php

namespace Typemill\Models;

class User extends WriteYaml
{
	public function getUsers()
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		/* check if users directory exists */
		if(!is_dir($userDir)){ return array(); }
		
		/* get all user files */
		$users = array_diff(scandir($userDir), array('..', '.'));
				
		$cleanUser	= array();
		foreach($users as $key => $user)
		{
			if($user == '.logins'){ continue; }
			$cleanUser[] = str_replace('.yaml', '', $user);
		}

		return $cleanUser;
	}

	# returns array of emails of all users
	public function getUserMails()
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		/* check if users directory exists */
		if(!is_dir($userDir)){ return array(); }
		
		/* get all user files */
		$users = array_diff(scandir($userDir), array('..', '.'));
		
		$usermails	= array();

		foreach($users as $key => $user)
		{
			if($user == '.logins'){ continue; }

			$contents 	= file_get_contents($userDir . DIRECTORY_SEPARATOR . $user);

			if($contents === false){ continue; }

			$searchfor 	= 'email:';

			# escape special characters in the query
			$pattern 	= preg_quote($searchfor, '/');
			
			# finalise the regular expression, matching the whole line
			$pattern 	= "/^.*$pattern.*\$/m";

			# search, and store first occurence in $matches
			if(preg_match($pattern, $contents, $match)){
				$usermails[] = trim(str_replace("email:", "", $match[0]));
			}
		}
		return $usermails;
	}

	public function findUserByEmail($email)
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		/* check if users directory exists */
		if(!is_dir($userDir)){ return array(); }
		
		/* get all user files */
		$users = array_diff(scandir($userDir), array('..', '.'));
		
		$usermails	= array();

		foreach($users as $key => $user)
		{
			if($user == '.logins'){ continue; }

			$contents 	= file_get_contents($userDir . DIRECTORY_SEPARATOR . $user);

			if($contents === false){ continue; }

			$searchfor 	= 'email:';

			# escape special characters in the query
			$pattern 	= preg_quote($searchfor, '/');
			
			# finalise the regular expression, matching the whole line
			$pattern 	= "/^.*$pattern.*\$/m";

			# search, and store first occurence in $matches
			if(preg_match($pattern, $contents, $match)){
				$usermail = trim(str_replace("email:", "", $match[0]));

				if($usermail == $email)
				{
					$user = \Symfony\Component\Yaml\Yaml::parse($contents);
					unset($user['password']);
					return $user;
				}
			}
		}
		return false;
	}
	
	public function getUser($username)
	{
		$user = $this->getYaml('settings/users', $username . '.yaml');
		return $user;
	}

	public function getSecureUser($username)
	{
		$user = $this->getYaml('settings/users', $username . '.yaml');
		unset($user['password']);
		return $user;
	}
	
	public function createUser($params)
	{
		$params['password'] = $this->generatePassword($params['password']);
	
		if($this->updateYaml('settings/users', $params['username'] . '.yaml', $params))
		{
			return $params['username'];
		}
		return false;
	}
	
	public function updateUser($params)
	{
		$userdata = $this->getUser($params['username']);
		
		# make sure passwords are not overwritten 
		if(isset($params['newpassword'])){ unset($params['newpassword']); }
		if(isset($params['password']))
		{
			if(empty($params['password']))
			{ 
				unset($params['password']); 
			}
			else
			{
				$params['password'] = $this->generatePassword($params['password']);
			}
		}
		
		$update = array_merge($userdata, $params);

		# cleanup data here
		
		
		$this->updateYaml('settings/users', $userdata['username'] . '.yaml', $update);

		# if user updated his own profile, update session data
		if(isset($_SESSION['user']) && $_SESSION['user'] == $params['username'])
		{
			$_SESSION['role'] 	= $update['userrole'];

			if(isset($update['firstname']))
			{
				$_SESSION['firstname'] = $update['firstname'];
			}
			if(isset($update['lastname']))
			{
				$_SESSION['lastname'] = $update['lastname'];
			}
		}
		
		return $userdata['username'];
	}
	
	public function deleteUser($username)
	{
		if($this->getUser($username))
		{
			unlink('settings/users/' . $username . '.yaml');
		}
	}
	
	/* replaced by ACL
	public function getUserroles()
	{
		return array('administrator', 'editor');
	}	
	*/

	public function login($username)
	{
		$user = $this->getUser($username);

		if($user)
		{
			$user['lastlogin'] = time();
			unset($user['password']);

			$_SESSION['user'] 	= $user['username'];
			$_SESSION['role'] 	= $user['userrole'];
			$_SESSION['login'] 	= $user['lastlogin'];

			if(isset($user['firstname']))
			{
				$_SESSION['firstname'] = $user['firstname'];
			}
			if(isset($user['lastname']))
			{
				$_SESSION['lastname'] = $user['lastname'];
			}
			
			# update user last login
			$this->updateUser($user);
		}
	}
	
	public function generatePassword($password)
	{
		return \password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
	}	
}