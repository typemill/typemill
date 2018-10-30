<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RedirectIfAuthenticated
{		
	protected $router;
	
	public function __construct(RouterInterface $router)
	{
		$this->router = $router;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		if(isset($_SESSION['login']))
		{
			$response = $response->withRedirect($this->router->pathFor('content.raw'));
		}
		
		return $next($request, $response);
	}
}