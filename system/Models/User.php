<?php

namespace Typemill\Models;

class User extends WriteYaml
{

	private $userDir = __DIR__ . '/../../settings/users';

	public function getUsers()
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		/* check if users directory exists */
		if(!is_dir($userDir)){ return array(); }
		
		/* get all user files */
		$userfiles = array_diff(scandir($userDir), array('..', '.', '.logins', 'tmuserindex-mail.txt', 'tmuserindex-role.txt'));
				
		$usernames	= array();
		foreach($userfiles as $key => $userfile)
		{
			$usernames[] = str_replace('.yaml', '', $userfile);
		}

		return $usernames;
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
			$this->deleteUserIndex();

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

		$this->deleteUserIndex();
	
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

			$this->deleteUserIndex();
		}
	}
	
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


	# accepts email with or without asterix and returns userdata
	public function findUsersByEmail($email)
	{
		# get all user files
		$usernames = $this->getUsers();

		$countusers = count($usernames);

		if($countusers == 0)
		{
			return false;
		}

		# use a simple dirty search if there are less than 10 users (only in use for new user registrations)
		if($countusers <= 4)
		{
			foreach($usernames as $key => $username)
			{
				$userdata = $this->getSecureUser($username);

				if($userdata['email'] == $email)
				{
					return $userdata;
				}
			}
			return false;
		}

		# if there are more than 10 users, search with an index
		$usermails = $this->getUserMailIndex($usernames);

		# search with starting asterix, ending asterix or without asterix
		if($email[0] == '*')
		{
			$userdata = [];
			$search = substr($email, 1);
			$length = strlen($search);

			foreach($usermails as $usermail => $username)
			{
				if(substr($usermail, -$length) == $search)
				{
					$userdata[] = $username;
				}
			}

			$userdata = empty($userdata) ? false : $userdata;

			return $userdata;
		}
		elseif(substr($email, -1) == '*')
		{
			$userdata = [];
			$search = substr($email, 0, -1);
			$length = strlen($search);

			foreach($usermails as $usermail => $username)
			{
				if(substr($usermail, 0, $length) == $search)
				{
					$userdata[] = $username;
				}
			}

			$userdata = empty($userdata) ? false : $userdata;

			return $userdata;
		}
		elseif(isset($usermails[$email]))
		{
			$userdata[] = $usermails[$email];
			return $userdata;
		}

		return false;
	}

	public function getUserMailIndex($usernames)
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		if(file_exists($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt'))
		{
			# read and return the file
			$usermailindex = file($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt');
		}
		
		$usermailindex	= array();

		foreach($usernames as $key => $username)
		{
			$userdata = $this->getSecureUser($username);

			$usermailindex[$userdata['email']] = $username;
		}

		file_put_contents($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt', var_export($usermailindex, TRUE));

		return $usermailindex;
	}

	# accepts email with or without asterix and returns usernames
	public function findUsersByRole($role)
	{
		# get all user files
		$usernames = $this->getUsers();

/*
		$countusers = count($usernames);

		if($countusers == 0)
		{
			return false;
		}

		# use a simple dirty search if there are less than 10 users (not in use right now)
		if($countusers <= 4)
		{
			$userdata = [];
			foreach($usernames as $key => $username)
			{
				$userdetails = $this->getSecureUser($username);

				if($userdetails['userrole'] == $role)
				{
					$userdata[] = $userdetails;
				}
			}
			if(empty($userdata))
			{
				return false;
			}

			return $userdata;
		}
*/
		$userroles = $this->getUserRoleIndex($usernames);

		if(isset($userroles[$role]))
		{
			return $userroles[$role];
		}

		return false;
	}

	public function getUserRoleIndex($usernames)
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		if(file_exists($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-role.txt'))
		{
			# read and return the file
			$userroleindex = file($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-role.txt');
		}
		
		$userroleindex = array();

		foreach($usernames as $key => $username)
		{
			$userdata = $this->getSecureUser($username);

			$userroleindex[$userdata['userrole']][] = $username;
		}

		file_put_contents($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-role.txt', var_export($userroleindex, TRUE));

		return $userroleindex;
	}

	protected function deleteUserIndex()
	{
		$userDir = __DIR__ . '/../../settings/users';
				
		if(file_exists($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt'))
		{
			# read and return the file
			unlink($userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt');
		}
	}	
}