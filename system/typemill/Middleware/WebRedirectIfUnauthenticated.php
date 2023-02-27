<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteParser;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Typemill\Models\User;

class WebRedirectIfUnauthenticated implements MiddlewareInterface
{
	public function __construct(RouteParser $router)
	{
		$this->router 	= $router;
	}

	public function process(Request $request, RequestHandler $handler) :response
	{
		# session authentication
		if(
			(isset($_SESSION['username'])) && 
			(isset($_SESSION['login']))
		)
		{
			# load userdata
			$user = new User();

			if($user->setUser($_SESSION['username']))
			{

 				# pass username and userrole
				$userdata = $user->getUserData();

				$request = $request->withAttribute('c_username', $userdata['username']);
				$request = $request->withAttribute('c_userrole', $userdata['userrole']);

			    # this executes code from routes first and then executes middleware
				$response = $handler->handle($request);

				return $response;
			}
		}

		# this executes only middleware code and not code from route
		$response = new Response();
		
		return $response->withHeader('Location', $this->router->urlFor('auth.show'))->withStatus(302);
	}
}