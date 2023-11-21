<?php

namespace Typemill\Static;

class Session
{
	public static function startSessionForSegments($sessionSegments, $routepath, $scheme)
	{
		if(isset($_SESSION))
		{
			return false; 
		}

		$routepath = ltrim($routepath, '/');

		foreach($sessionSegments as $segment)
		{
			if(substr( $routepath, 0, strlen($segment) ) === ltrim($segment, '/'))
			{
				# configure session
				ini_set('session.cookie_httponly', 1 );
				ini_set('session.use_strict_mode', 1);
				ini_set('session.cookie_samesite', 'lax');

				if($scheme == 'https')
				{
					ini_set('session.cookie_secure', 1);
					session_name('__Secure-typemill-session');
				}
				else
				{
					session_name('typemill-session');
				}

				# start session
				session_start();

				return true;
			}
		}

		return false;
	}

	public static function stopSession()
	{
		if(isset($_SESSION))
		{
			# Unset all of the session variables.
			$_SESSION = array();

			# If it's desired to kill the session, also delete the session cookie. This will destroy the session, and not just the session data
			if (ini_get("session.use_cookies"))
			{
				$params = session_get_cookie_params();
			
				setcookie(
					session_name(), 
					'', 
					time() - 42000,
					$params["path"],
					$params["domain"],
					$params["secure"], 
					$params["httponly"]
				);
			}

			# Finally, destroy the session.
			session_destroy();
		}
	}
}