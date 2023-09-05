<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteParser;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class ApiAuthorization implements MiddlewareInterface
{
	public function __construct($acl, string $resource = NULL, string $action = NULL)
	{
		$this->acl 			= $acl;
		$this->resource 	= $resource;
		$this->action 		= $action;		
	}

	public function process(Request $request, RequestHandler $handler) :Response
	{
		if(!$this->acl->isAllowed($request->getAttribute('c_userrole'), $this->resource, $this->action))
		{
			$message = 'userrole: ' . $request->getAttribute('c_userrole') . ' resource: ' . $this->resource . ' action: ' . $this->action;
			$response = new Response();
			
			$response->getBody()->write(json_encode([
				'message' => $message
			]));

			return $response->withStatus(403);			
		}
	
		$response = $handler->handle($request);
	
		return $response;
	}
}