<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Flash\Messages;

class FlashMessages implements MiddlewareInterface
{

	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	public function process(Request $request, RequestHandler $handler) :response
	{
		if(is_array($request->getAttribute('session')))
		{
			echo '<br> flash messages';

			$this->container->set('flash', function(){
				return new Messages();
			});
		}

		return $handler->handle($request);
	}
}