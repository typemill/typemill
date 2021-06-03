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
			return $response->withJson(['data' => false, 'errors' => ['message' => 'You are probably logged out. Please login and try again.']], 403);
		}

		# check csrf protection
	    if( $request->getattribute('csrf_result') === false )
	    {
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'The form has a timeout. Please reload the page and try again.']), 403);
	    }
		return $next($request, $response);
	}
}