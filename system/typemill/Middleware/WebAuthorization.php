<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteParser;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class WebAuthorization implements MiddlewareInterface
{

	private $router;

	private $acl;

	private $resource;

	private $action;
	
	public function __construct(RouteParser $router, $acl, string $resource = NULL, string $action = NULL)
	{
		$this->router 		= $router;
		$this->acl 			= $acl;
		$this->resource 	= $resource;
		$this->action 		= $action;		
	}

	public function process(Request $request, RequestHandler $handler) :Response
	{
		$test = $this->acl->isAllowed($request->getAttribute('c_userrole'), $this->resource, $this->action);
		
		if(!$this->acl->isAllowed($request->getAttribute('c_userrole'), $this->resource, $this->action))
		{
			$response = new Response();

			return $response->withHeader('Location', $this->router->urlFor('user.account'))->withStatus(302);
		}

		$response = $handler->handle($request);
	
		return $response;
	}
}