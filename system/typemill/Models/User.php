<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;

class User
{
	private $userDir;

	private $yaml;

	private $user = false;

	public $error = false;

	public function __construct()
	{
		$this->userDir 	= getcwd() . '/settings/users';
		$this->storage 	= new StorageWrapper('\Typemill\Models\Storage');
	}

	public function setUser(string $username)
	{
		$this->user = $this->storage->getYaml('settingsFolder', 'users', $username . '.yaml');
	
		if(!$this->user)
		{
			$this->error = 'User not found';

			return false;
		}
		
		# delete password from public userdata
		unset($this->user['password']);

		return $this;
	}

	public function setUserWithPassword(string $username)
	{
		$this->user = $this->storage->getYaml('settingsFolder', 'users', $username . '.yaml');

		if(!$this->user)
		{
			$this->error = 'User not found.';

			return false;
		}
		
		return $this;
	}

	public function setValue($key, $value)
	{
		$this->user[$key] = $value;
	}

	public function unsetValue($key)
	{
		unset($this->user[$key]);
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
	
		if($this->storage->updateYaml('settingsFolder', 'users', $params['username'] . '.yaml', $params))
		{
			$this->deleteUserIndex();

			return true;
		}

		$this->error = $this->storage->getError();

		return false;
	}

	public function updateUser()
	{		
		if($this->storage->updateYaml('settingsFolder', 'users', $this->user['username'] . '.yaml', $this->user))
		{
			$this->deleteUserIndex();
	
			return true;
		}

		$this->error = $this->storage->getError();

		return false;
	}

	public function deleteUser()
	{
		if($this->storage->deleteFile('settingsFolder', 'users', $this->user['username'] . '.yaml'))
		{
			$this->deleteUserIndex();

			return true;
		}

		$this->error = $this->storage->getError();		

		return false;
	}

	public function getUserFields($acl, $userrole, $inspectorrole = NULL)
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$userfields 	= $storage->getYaml('systemSettings', '', 'user.yaml');

		if(!$inspectorrole)
		{
			# if there is no inspector-role we assume that it is the same role like the userrole 
			# for example account is always visible by the same user
			# edit user can be done by another user like admin.
			$inspectorrole = $userrole;
		}

		# if a plugin with a role has been deactivated, then users with the role throw an error, so set them back to member...
		if(!$acl->hasRole($userrole))
		{
			$userrole = 'member';
		}

		# dispatch fields;
		#$fields = $this->c->dispatcher->dispatch('onUserfieldsLoaded', new OnUserfieldsLoaded($fields))->getData();

		# only roles who can edit content need profile image and description
		if($acl->isAllowed($userrole, 'mycontent', 'create'))
		{
			$newfield['image'] 			= ['label' => 'Profile-Image', 'type' => 'image'];
			$newfield['description'] 	= ['label' => 'Author-Description (Markdown)', 'type' => 'textarea'];
			
			$userfields = array_slice($userfields, 0, 1, true) + $newfield + array_slice($userfields, 1, NULL, true);
			# array_splice($fields,1,0,$newfield);
		}

		# Only admin ...
		if($acl->isAllowed($inspectorrole, 'user', 'write'))
		{
			# can change userroles
			$definedroles = $acl->getRoles();
			$options = [];

			# we need associative array to make select-field with key/value work
			foreach($definedroles as $role)
			{
				$options[$role] = $role;
 			}

			$userfields['userrole'] = ['label' => 'Role', 'type' => 'select', 'options' => $options];

			# can activate api access
			$userfields['apiaccess'] = ['label' => 'API access', 'checkboxlabel' => 'Activate API access for this user. Use username and password for api calls.', 'type' => 'checkbox'];
		}

		return $userfields;
	}

	public function login()
	{
		if($this->user)
		{
			$this->user['lastlogin'] = time();

			$_SESSION['username'] 	= $this->user['username'];
			$_SESSION['login'] 		= $this->user['lastlogin'];

			if(isset($this->user['recovertoken']) OR isset($this->user['recoverdate']))
			{
				$this->unsetValue('recovertoken');
				$this->unsetValue('recoverdate');
			}

			# update user last login
			$this->updateUser();
		}
	}
	
	public function generatePassword($password)
	{
		return \password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
	}

/*
	public function getBasicAuth()
	{
		$basicauth = $this->user['username'] . ":" . $this->user['internalApiKey'];

		return base64_encode($basicauth);		
	}
*/

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
}