<?php

namespace Typemill\Static;

class Session
{
	public static function startSessionForSegments($sessionSegments, $routepath)
	{
		$routepath = ltrim($routepath, '/');

		foreach($sessionSegments as $segment)
		{
			#echo '<br>' . $segment;
			#echo '<br>' . $routepath;
			if(substr( $routepath, 0, strlen($segment) ) === ltrim($segment, '/'))
			{
				#echo '<br>Create Session';

				# configure session
				ini_set('session.cookie_httponly', 1 );
				ini_set('session.use_strict_mode', 1);
				ini_set('session.cookie_samesite', 'lax');

				/*
				if($uri->getScheme() == 'https')
				{
					ini_set('session.cookie_secure', 1);
					session_name('__Secure-typemill-session');
				}
				else
				{
					session_name('typemill-session');
				}
				*/              

				# start session
				session_start();

				break;
			}
		}
	}
}