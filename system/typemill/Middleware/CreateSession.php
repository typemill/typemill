<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

class CreateSession implements MiddlewareInterface
{
	protected $sessionSegments = [];

	protected $routepath = false;

	public function __construct($session_segments, $routepath)
	{
		$this->sessionSegments = $session_segments;

		$this->routepath = $routepath;
	}

	public function process(Request $request, RequestHandler $handler) :response
	{        
		foreach($this->sessionSegments as $segment)
		{
			if(substr( $this->routepath, 0, strlen($segment) ) === ltrim($segment, '/'))
			{
				echo '<br>Create Session';

				// configure session
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
				// start session
				session_start();

			   $request = $request->withAttribute('session', $_SESSION);
			}
		}

		return $handler->handle($request);
	}
}