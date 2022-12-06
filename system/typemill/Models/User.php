<?php

namespace Typemill\Models;

use Typemill\Models\Yaml;

class User
{
	private $userDir;

	private $yaml;

	private $user = false;

	private $password = false;

	public $error = false;

	public function __construct()
	{
		$this->userDir 	= getcwd() . '/settings/users';
		$this->yaml 	= new Yaml('\Typemill\Models\Storage');
	}

	public function setUser(string $username)
	{
		# if no user is set or requested user has a different username
#		if(!$this->user OR ($this->user['username'] != $username))
#		{
			$this->user = $this->yaml->getYaml('settings/users', $username . '.yaml');
		
			if(!$this->user)
			{
				$this->error = 'User not found';

				return false;
			}
			
			# store password in private property so it is not accessible outside			
			$this->password = $this->user['password'];

			# delete password from public userdata
			unset($this->user['password']);
#		}

		return $this;
	}

	public function setUserWithPassword(string $username)
	{
		if(!$this->user)
		{
			$this->user = $this->yaml->getYaml('settings/users', $username . '.yaml');

			if(!$this->user)
			{
				$this->error = 'User not found.';

				return false;
			}
		}
		
		return $this;
	}

	public function getUserData()
	{
		return $this->user;
	}

	public function getError()
	{
		return $this->error;
	}

	public function getAllUsers()
	{				
		# check if users directory exists
		if(!is_dir($this->userDir))
		{
			$this->error = "Directory $this->userDir does not exist."; 
			
			return false;
		}
		
		# get all user files
		$userfiles = array_diff(scandir($this->userDir), array('..', '.', '.logins', 'tmuserindex-mail.txt', 'tmuserindex-role.txt'));

		$usernames	= [];

		if(!empty($userfiles))
		{
			foreach($userfiles as $key => $userfile)
			{
				$usernames[] = str_replace('.yaml', '', $userfile);
			}

			usort($usernames, 'strnatcasecmp');
		}

		return $usernames;
	}

	public function createUser(array $params)
	{
		$params['password'] = $this->generatePassword($params['password']);
	
		if($this->yaml->updateYaml('settings/users', $params['username'] . '.yaml', $params))
		{
			$this->deleteUserIndex();

			return $params['username'];
		}

		$this->error = $this->yaml->getError();

		return false;
	}

	public function setValue($key, $value)
	{
		$this->user[$key] = $value;
	}

	public function unsetValue($key)
	{
		unset($this->user[$key]);
	}

	public function updateUser()
	{
		if($this->yaml->updateYaml('settings/users', $this->user['username'] . '.yaml', $this->user))
		{
			$this->deleteUserIndex();
	
			return true;
		}

		$this->error = $this->yaml->getError();

		return false;
	}











	public function unsetFromUser(array $keys)
	{
		if(empty($keys) OR !$this->user)
		{
			$this->error = 'Keys are empty or user does not exist.';

			return false;
		}
		
		foreach($keys as $key)
		{
			if(isset($this->user[$key]))
			{
				unset($this->user[$key]);
			}
		}
	
		$this->yaml->updateYaml('settings/users', $this->user['username'] . '.yaml', $this->user);
		
		return true;
	}

	public function updateUserOld()
	{
		# add password back to userdata before you store/update user
		if($this->password)
		{
			$this->user['password'] = $this->password;
		}
		
		if($this->yaml->updateYaml('settings/users', $this->user['username'] . '.yaml', $this->user))
		{
			return true;
		}

		$this->error = $this->yaml->getError();

		return false;
	}


	public function updateUserWithInput(array $input)
	{
		if(!isset($input['username']) OR !$this->user)
		{
			return false;
		}
		
		# make sure new password is not stored 
		if(isset($input['newpassword']))
		{ 
			unset($input['newpassword']); 
		}

		# make sure password is set correctly
		if(isset($input['password']))
		{
			if(empty($input['password']))
			{
				unset($input['password']); 
			}
			else
			{
				$input['password'] = $this->generatePassword($input['password']);
			}
		}
		
		# set old password back to original userdate
		if($this->password)
		{
			$this->user['password'] = $this->password;
		}

		# overwrite old userdata with new userdata
		$updatedUser = array_merge($this->user, $input);

		# cleanup data here
		
		$this->updateYaml('settings/users', $this->user['username'] . '.yaml', $updatedUser);

		$this->deleteUserIndex();
	
		# if user updated his own profile, update session data
		if(isset($_SESSION['username']) && $_SESSION['username'] == $input['username'])
		{
			$_SESSION['userrole'] 	= $updatedUser['userrole'];

			if(isset($updatedUser['firstname']))
			{
				$_SESSION['firstname'] = $updatedUser['firstname'];
			}
			if(isset($updatedUser['lastname']))
			{
				$_SESSION['lastname'] = $updatedUser['lastname'];
			}
		}
		
		return $this->user['username'];
	}
	
	public function deleteUser(string $username)
	{
		if($this->getUser($username))
		{
			unlink('settings/users/' . $username . '.yaml');

			$this->deleteUserIndex();

			return true;
		}
		return false;
	}
	
	public function login()
	{
		if($this->user)
		{
			$this->user['lastlogin'] = time();
#			$this->user['internalApiKey'] = bin2hex(random_bytes(32));

			$_SESSION['username'] 	= $this->user['username'];
#			$_SESSION['userrole'] 	= $this->user['userrole'];
			$_SESSION['login'] 		= $this->user['lastlogin'];
/*
			if(isset($this->user['firstname']))
			{
				$_SESSION['firstname'] = $this->user['firstname'];
			}
			if(isset($this->user['lastname']))
			{
				$_SESSION['lastname'] = $this->user['lastname'];
			}
*/			
			if(isset($this->user['recovertoken']) OR isset($this->user['recoverdate']))
			{
				$this->unsetFromUser($this->user['username'], ['recovertoken', 'recoverdate']);
			}

			# update user last login
			$this->updateUser();			
		}
	}
	
	public function generatePassword($password)
	{
		return \password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
	}

	public function getBasicAuth()
	{
		$basicauth = $this->user['username'] . ":" . $this->user['internalApiKey'];

		return base64_encode($basicauth);		
	}

	# accepts email with or without asterix and returns userdata
	public function findUsersByEmail($email)
	{
		$usernames = [];

		# Make sure that we scan only the first 11 files even if there are some thousand users.
		if ($dh = opendir($this->userDir))
		{
			$count 		= 0;
			$exclude	= array('..', '.', '.logins', 'tmuserindex-mail.txt', 'tmuserindex-role.txt');

		    while ( ($userfile = readdir($dh)) !== false && $count <= 10 ) 
		    {
		    	if(in_array($userfile, $exclude)){ continue; }

				$usernames[] = str_replace('.yaml', '', $userfile);
		    	$count++;
		    }

		    closedir($dh);
		}

		if(count($usernames) == 0)
		{
			return false;
		}
		elseif(count($usernames) <= 9)
		{
			# perform a simple search because we have less than 10 registered users
			return $this->searchEmailSimple($usernames,$email);
		}
		else
		{
			# perform search in an index for many users
			return $this->searchEmailByIndex($email);
		}
	}

	private function searchEmailSimple($usernames, $email)
	{
		foreach($usernames as $username)
		{
			$this->setUser($username);
			$user = $this->getUserData();

			if($user['email'] == $email)
			{
				return [$username];
			}
		}
		return false;
	}

	private function searchEmailByIndex($email)
	{
		# if there are more than 10 users, search with an index
		$usermails 	= $this->getUserMailIndex();
		$usernames 	= [];

		# search with starting asterix, ending asterix or without asterix
		if($email[0] == '*')
		{
			$search = substr($email, 1);
			$length = strlen($search);

			foreach($usermails as $usermail => $username)
			{
				if(substr($usermail, -$length) == $search)
				{
					$usernames[] = $username;
				}
			}
		}
		elseif(substr($email, -1) == '*')
		{
			$search = substr($email, 0, -1);
			$length = strlen($search);

			foreach($usermails as $usermail => $username)
			{
				if(substr($usermail, 0, $length) == $search)
				{
					$usernames[] = $username;
				}
			}
		}
		elseif(isset($usermails[$email]))
		{
			$usernames[] = $usermails[$email];
		}

		if(empty($usernames))
		{
			return false;
		}

		return $usernames;
	}

	public function getUserMailIndex()
	{
		if(file_exists($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt'))
		{
			# unserialize and return the file
			$usermailindex = unserialize(file_get_contents($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt'));

			if($usermailindex)
			{
				return $usermailindex;
			}
		}
		
		$usernames 		= $this->getAllUsers();
		$usermailindex	= [];

		foreach($usernames as $username)
		{
			$this->setUser($username);
			$userdata = $this->getUserData();

			$usermailindex[$userdata['email']] = $username;
		}

		file_put_contents($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt', serialize($usermailindex));

		return $usermailindex;
	}

	public function findUsersByRole($role)
	{
		$userroles = $this->getUserRoleIndex();

		if(isset($userroles[$role]))
		{
			return $userroles[$role];
		}

		return false;
	}

	public function getUserRoleIndex()
	{
		if(file_exists($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-role.txt'))
		{
			# unserialize and return the file
			$userroleindex = unserialize(file_get_contents($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-role.txt'));
			if($userroleindex)
			{
				return $userroleindex;
			}
		}

		$usernames		= $this->getAllUsers();
		$userroleindex 	= [];

		foreach($usernames as $username)
		{
			$userdata = $this->getSecureUser($username);

			$userroleindex[$userdata['userrole']][] = $username;
		}

		file_put_contents($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-role.txt', serialize($userroleindex));

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

	# deprecated
	public function getSecureUser(string $username)
	{
		$user = $this->getYaml('settings/users', $username . '.yaml');
		unset($user['password']);
		return $user;
	}	
}