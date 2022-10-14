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
		$this->userDir 	= getcwd() . '/system/settings/users';
		$this->yaml 	= new Yaml('\Typemill\Models\Storage');
	}

	public function setUser(string $username)
	{
		if(!$this->user)
		{
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
		}

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

	public function getError()
	{
		return $this->error;
	}

	public function getUserData()
	{
		return $this->user;
	}

	public function getAllUsers()
	{				
		# check if users directory exists
		if(!is_dir($this->userDir))
		{
			$this->error = 'Directory $this->userDir does not exist.'; 
			
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

	public function updateUser()
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
		$usernames 	= [];
		
		# Make sure that we scan only the first 11 files even if there are some thousand users.
		if ($dh = opendir($this->userDir))
		{
			$count 		= 1;
			$exclude	= array('..', '.', '.logins', 'tmuserindex-mail.txt', 'tmuserindex-role.txt');

		    while ( ($userfile = readdir($dh)) !== false && $count <= 11 ) 
		    {
		    	if(in_array($userfile, $exclude)){ continue; }

				$usernames[] = str_replace('.yaml', '', $userfile);
		    	$count++;
		    }

		    closedir($dh);
		}

		$countusers = count($usernames);

		if($countusers == 0)
		{
			return false;
		}

		# use a simple dirty search if there are less than 10 users (only in use for new user registrations)
		if($countusers <= 10)
		{
			foreach($usernames as $username)
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
		$usermails = $this->getUserMailIndex();

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
		
		$usernames 		= $this->getUsers();
		$usermailindex	= [];

		foreach($usernames as $username)
		{
			$userdata = $this->getSecureUser($username);

			$usermailindex[$userdata['email']] = $username;
		}

		file_put_contents($this->userDir . DIRECTORY_SEPARATOR . 'tmuserindex-mail.txt', serialize($usermailindex));

		return $usermailindex;
	}

	# accepts email with or without asterix and returns usernames
	public function findUsersByRole($role)
	{

/*
		# get all user files
		$usernames = $this->getUsers();

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

		$usernames		= $this->getUsers();
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