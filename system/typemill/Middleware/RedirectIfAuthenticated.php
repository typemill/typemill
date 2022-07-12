<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
# use Slim\Routing\RouteContext;
use Slim\Routing\RouteParser;

class RedirectIfAuthenticated implements MiddlewareInterface
{			
	public function __construct(RouteParser $router, $settings)
	{
		$this->router 	= $router;
		$this->settings = $settings;
	}

	public function process(Request $request, RequestHandler $handler) :response
	{		
		$response = $handler->handle($request);

		$editor = (isset($this->settings['editor']) && $this->settings['editor'] == 'visual') ? 'visual' : 'raw';

		if(isset($_SESSION['login']))
		{
			return $response->withHeader('Location', $this->router->pathFor('content.' . $editor))->withStatus(302);
#			$response = $response->withRedirect($this->router->pathFor('content.' . $editor));
		}
		
		return $response;
	}
}