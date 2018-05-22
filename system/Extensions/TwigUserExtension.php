<?php

namespace Typemill\Extensions;

class TwigUserExtension extends \Twig_Extension
{	
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('is_role', array($this, 'isRole' )),
			new \Twig_SimpleFunction('get_username', array($this, 'getUsername' ))
		];
	}
	
	public function isRole($role)
	{
		if(isset($_SESSION['role']) && $_SESSION['role'] == $role)
		{
			return true;
		}
		
		return false;
	}
	
	public function getUsername()
	{
		if(isset($_SESSION['user']))
		{
			return $_SESSION['user'];
		}
		
		return false;
	}
}