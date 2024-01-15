<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class RemoveCredentialsMiddleware implements MiddlewareInterface
{
	public function process(Request $request, RequestHandler $handler) :response
	{
		$uri = $request->getUri();

		# Remove user information (username:password) from the URI
		$uri = $uri->withUserInfo('');

		# Create a new request with the modified URI
		$request = $request->withUri($uri);

		# we could add basic auth credentials to request for later usage

		$response = $handler->handle($request);
	
		return $response;
	}
}