<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\Twig;

class FlashMessages
{	
	public function __construct(Twig $view)
	{
		$this->view = $view;
	}
	
	public function __invoke(Request $request, RequestHandler $handler)
	{
		if(isset($_SESSION['slimFlash']) && is_array($_SESSION['slimFlash']))
		{
			$this->view->getEnvironment()->addGlobal('flash', $_SESSION['slimFlash']);
			
			unset($_SESSION['slimFlash']);
		}

		return $handler->handle($request);
	}
}