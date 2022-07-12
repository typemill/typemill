<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteContext;
use Slim\Csrf\Guard;

class CsrfProtection implements MiddlewareInterface
{
	protected $container;

	protected $responseFactory;

	public function __construct($container, $responseFactory)
	{
		$this->container 		= $container;
		$this->responseFactory 	= $responseFactory;
	}

	public function process(Request $request, RequestHandler $handler) :response
	{
		if(is_array($request->getAttribute('session')))
		{
			echo '<br> csrf protection';

			$responseFactory = $this->responseFactory;

			# Register Middleware On Container
			$this->container->set('csrf', function () use ($responseFactory)
			{
				return new Guard($responseFactory); 
			});
		}

		return $handler->handle($request);
	}
}