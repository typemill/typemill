<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class accessController
{
	protected $router;
	
	public function __construct(RouterInterface $router, $acl, $resource, $privilege)
	{
		$this->router 		= $router;
		$this->acl 			= $acl;
		$this->resource 	= $resource;
		$this->privilege 	= $privilege;
	}

	public function __invoke(Request $request, Response $response, $next)
	{

		if($this->resource == NULL && $this->privilege == NULL)
		{
			return $next($request, $response);
		}

		if(!isset($_SESSION['login']))
		{
			return $response->withRedirect($this->router->pathFor('auth.show'));
		}

		if(!$this->acl->isAllowed($_SESSION['role'], $this->resource, $this->privilege ))
		{
			# redirect to frontend startpage
			# alternatively return an error and show an error page.
			return $response->withRedirect($this->router->pathFor('home'));
		}

		return $next($request, $response);
	}
}