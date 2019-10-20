<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RedirectIfUnauthenticated
{
	protected $router;
	
	public function __construct(RouterInterface $router, $flash)
	{
		$this->router = $router;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		if(!isset($_SESSION['login']))
		{
			return $response->withRedirect($this->router->pathFor('auth.show'));
		}

		return $next($request, $response);
	}
}