<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

class JsonBodyParser implements MiddlewareInterface
{
	public function process(Request $request, RequestHandler $handler) :response
	{
		#echo '<br> JSON Body parser';

		$contentType = $request->getHeaderLine('Content-Type');

		if (strstr($contentType, 'application/json')) 
		{
			$contents = json_decode(file_get_contents('php://input'), true);
			if (json_last_error() === JSON_ERROR_NONE) 
			{
				$request = $request->withParsedBody($contents);
			}
		}

		return $handler->handle($request);
	}
}
