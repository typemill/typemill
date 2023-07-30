<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\Twig;

class OldInputMiddleware
{
	protected $view;
	
	public function __construct(Twig $view)
	{
		$this->view = $view;
	}
	
	public function __invoke(Request $request, RequestHandler $handler)
	{		
		if(isset($_SESSION))
		{
			if(isset($_SESSION['old']))
			{
				$this->view->getEnvironment()->addGlobal('old', $_SESSION['old']);
			}
			if(!empty($request->getParsedBody()))
			{
				$_SESSION['old'] = $request->getParsedBody();
			}
		}
		
		$response = $handler->handle($request);
	
		return $response;
	}
}