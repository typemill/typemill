<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteContext;
use Slim\Csrf\Guard;

class CsrfProtectionToMiddleware
{
	protected $container;

	public function __construct($container)
	{
		$this->container 		= $container;
	}

	public function __invoke(Request $request, RequestHandler $handler)
	{
		if(is_array($request->getAttribute('session')))
		{
			echo '<br> csrf protection to middleware';
			
			return $this->container->get('csrf');
		}
	}
}