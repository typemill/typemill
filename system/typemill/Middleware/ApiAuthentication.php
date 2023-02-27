<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;
use Slim\Psr7\Response;
use Typemill\Models\User;

class ApiAuthentication
{
	public function __invoke(Request $request, RequestHandler $handler)
	{
	    $routeContext 	= RouteContext::fromRequest($request);
	    $baseURL 		= $routeContext->getBasePath();

	    # check if it is a session based authentication
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

					$request = $request->withAttribute('c_username', $userdata['username']);
					$request = $request->withAttribute('c_userrole', $userdata['userrole']);

					$response = $handler->handle($request);

					return $response;
				}
			}
			else
			{
				# return error message
			}
		}


		# api authentication with basic auth
		# inspired by tuupola
		$host 			= $request->getUri()->getHost();
		$scheme 		= $request->getUri()->getScheme();
		$server_params 	= $request->getServerParams();

		/*
    	# HTTP allowed only if secure is false or server is in relaxed array.
		# use own logic for https proto forwarding
		if($scheme !== "https" && $this->options["secure"] !== true)
		{
			$allowedHost = in_array($host, $this->options["relaxed"]);

			# if 'headers' is in the 'relaxed' key, then we check for forwarding
			$allowedForward = false;
			if (in_array("headers", $this->options["relaxed"]))
			{
				if ( $request->getHeaderLine("X-Forwarded-Proto") === "https" && $request->getHeaderLine('X-Forwarded-Port') === "443")
				{
					$allowedForward = true;
				}
			}

			if (!($allowedHost || $allowedForward))
			{
				$message = sprintf("Insecure use of middleware over %s denied by configuration.", strtoupper($scheme));
				throw new \RuntimeException($message);
			}
		}
		*/

		$params = [];

		if (preg_match("/Basic\s+(.*)$/i", $request->getHeaderLine("Authorization"), $matches)) 
		{
			$explodedCredential = explode(":", base64_decode($matches[1]), 2);
			if (count($explodedCredential) == 2)
			{
				[$params["user"], $params["password"]] = $explodedCredential;
			}
		}

		if(!empty($params))
		{
			# load userdata
			$user = new User();

			if($user->setUserWithPassword($params['user']))
			{
				$userdata 	= $user->getUserData();

				# this might be unsecure, check for === comparator
				$apiaccess  = ( isset($userdata['apiaccess']) && $userdata['apiaccess'] == true ) ? true : false;

				if($userdata && $apiaccess && password_verify($params['password'], $userdata['password']))
				{
					$request = $request->withAttribute('c_username', $userdata['username']);
					$request = $request->withAttribute('c_userrole', $userdata['userrole']);

				    # this executes code from routes first and then executes middleware
					$response = $handler->handle($request);

					return $response;
				}
				else
				{
					# if basic auth is set but with wrong credentials
					$response = new Response();
					
					$response->getBody()->write(json_encode([
						'message' => 'Authentication failed.'
					]));

					return $response->withHeader('WWW-Authenticate', 'Basic realm=')->withStatus(401);
				}
			}
		}

#		elseif ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
		    # if you use this, then all xhr-calls need a session. 
		    # no direct xhr calls without session are possible
		    # might increase security, but can have unwanted cases e.g. when you 
		    # want to provide public api accessible for all by javascript (do you ever want??)
#		}

		$response = new Response();

		$response->getBody()->write('Zugriff nicht erlaubt.');

		return $response->withStatus(401);
	}
}