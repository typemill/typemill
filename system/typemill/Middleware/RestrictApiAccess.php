<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;
use Slim\Psr7\Response;
use Typemill\Models\User;

class RestrictApiAccess
{
	public function __invoke(Request $request, RequestHandler $handler)
	{
	    $routeContext 	= RouteContext::fromRequest($request);
	    $baseURL 		= $routeContext->getBasePath();

	    # check if it a session based authentication
		if ($request->hasHeader('X-Session-Auth'))
		{
			session_start();

			$authenticated = ( 
					(isset($_SESSION['username'])) && 
					(isset($_SESSION['login'])) 
				)
				? true : false;

			if($authenticated)
			{
				# here we have to load userdata and pass them through request or response
				$user = new User();

				if($user->setUser($_SESSION['username']))
				{
					$userdata = $user->getUserData();

					$request = $request->withAttribute('username', $userdata['username']);
					$request = $request->withAttribute('userrole', $userdata['userrole']);

					$response = $handler->handle($request);

					return $response;
				}
			}
		}

#		elseif ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
		    # if you use this, then all xhr-calls need a session. 
		    # no direct xhr calls without session are possible
		    # might increase security, but can have unwanted cases e.g. when you 
		    # want to provide public api accessible for all by javascript (do you ever want??)
#		}

		# this is for api-key authentication
	    $user 	= isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
	    $apikey = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;

	    if($user && $apikey)
	    {
		    # get user with username
	    	# or get user with apikey

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