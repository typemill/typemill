<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;
use Slim\Psr7\Response;

class RestrictApiAccess
{
	public function __invoke(Request $request, RequestHandler $handler)
	{
	    $routeContext 	= RouteContext::fromRequest($request);
	    $baseURL 		= $routeContext->getBasePath();

		if ($request->hasHeader('X-Session-Auth')) {

			session_start();

			$authenticated = ( 
					(isset($_SESSION['username'])) && 
					(isset($_SESSION['userrole'])) && 
					(isset($_SESSION['login'])) 
				)
				? true : false;

			if($authenticated)
			{
				$response = $handler->handle($request);

				return $response;
			}
		}

#		elseif ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
		    # advantage: all xhr-calls to the api will be session based
		    # no direct calls from javascript possible
		    # only from server
#		}


	    $user 	= isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
	    $apikey = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;

	    if($user && $apikey)
	    {
		    # get user
		    # check if user has tmpApiKey
		    # check if user has permanentApiKey
		    # check if user has tmpApiKey
		    # check if tmpApiKey has expired
		    # check if user keys are correct

			$response = $handler->handle($request);

			return $response;
	    }

		$response = new Response();

		$response->getBody()->write('Zugriff nicht erlaubt.');

		return $response->withStatus(401);
	}
}