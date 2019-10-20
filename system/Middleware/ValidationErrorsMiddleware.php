<?php

namespace Typemill\Middleware;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;

class ValidationErrorsMiddleware
{	
	protected $view;
	
	public function __construct(Twig $view)
	{
		$this->view = $view;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		if(isset($_SESSION['errors']))
		{
			$this->view->getEnvironment()->addGlobal('errors', $_SESSION['errors']);
			
			unset($_SESSION['errors']);
		}
		
		return $next($request, $response);
	}
}