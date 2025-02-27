<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Typemill\Models\User;

class TwigUserExtension extends AbstractExtension
{	
	protected $acl;

	public function __construct($acl)
	{
		$this->acl 		= $acl;
	}

	public function getFunctions()
	{
		return [
			new \Twig\TwigFunction('get_username', array($this, 'getUsername' )),
			new \Twig\TwigFunction('is_loggedin', array($this, 'isLoggedin' )),
			new \Twig\TwigFunction('is_allowed', array($this, 'isAllowed' )),
			new \Twig\TwigFunction('is_role', array($this, 'isRole' )),
			new \Twig\TwigFunction('get_role', array($this, 'getRole' )),
		];
	}
	
	public function isLoggedin()
	{
		if(isset($_SESSION['login']) && $_SESSION['login'])
		{
			return true;
		}
		
		return false;
	}

	public function getUsername()
	{
		if(isset($_SESSION['username']))
		{
			return $_SESSION['username'];
		}

		return false;
	}

	public function isRole($role)
	{
		if(isset($_SESSION['username']))
		{
			$username = $_SESSION['username'];

			$usermodel = new User();
			$user = $usermodel->setUser($username);

			if($user)
			{
				$userrole = $usermodel->getValue('userrole');
				if($userrole === $role)
				{
					return true;
				}
			}
		}
		
		return false;
	}

	public function getRole()
	{
		if(isset($_SESSION['username']))
		{
			$username = $_SESSION['username'];

			$usermodel = new User();
			$user = $usermodel->setUser($username);

			if($user)
			{
				$userrole = $usermodel->getValue('userrole');
				return $userrole;
			}
		}

		return false;
	}

	public function isAllowed($resource, $action)
	{
		if(isset($_SESSION['username']))
		{
			$username = $_SESSION['username'];
			$usermodel = new User();
			$user = $usermodel->setUser($username);

			if($user)
			{
				$userrole = $usermodel->getValue('userrole');

				if($this->acl->isAllowed($userrole, $resource, $action))
				{
					return true;
				}
			}
		}

		return false;
	}
}