<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RestrictApiAccess
{
	protected $router;
	
	public function __construct(RouterInterface $router)
	{
		$this->router = $router;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		if(!isset($_SESSION['login']) || !isset($_SESSION['role']))
		{
			return $response->withJson(['errors' => ['access denied']], 403);
		}
		return $next($request, $response);
	}
}