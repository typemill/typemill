<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RedirectIfNoAdmin
{	
	protected $router;
	
	public function __construct(RouterInterface $router, $flash)
	{
		$this->router = $router;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		if(!isset($_SESSION['login']) || !isset($_SESSION['role']))
		{
			$response = $response->withRedirect($this->router->pathFor('auth.show'));
		}
		
		if($_SESSION['role'] != 'administrator')
		{
			$response = $response->withRedirect($this->router->pathFor('content.raw'));			
		}
		
		return $next($request, $response);
	}
}