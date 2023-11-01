<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteParser;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Typemill\Static\Translations;

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
			$message = Translations::translate('Permission denied') . '. ';
			$message .= Translations::translate('Your are an ');
			$message .= $request->getAttribute('c_userrole'); 
			$message .= Translations::translate(' and you cannot '); 
			$message .= $this->action . Translations::translate(' this ') . $this->resource;

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