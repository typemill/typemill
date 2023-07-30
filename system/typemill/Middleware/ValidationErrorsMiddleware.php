<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\Twig;

class ValidationErrorsMiddleware
{	
	protected $view;
	
	public function __construct(Twig $view)
	{
		$this->view = $view;
	}

	public function __invoke(Request $request, RequestHandler $handler)
	{
		if(isset($_SESSION['errors']))
		{
			$this->view->getEnvironment()->addGlobal('errors', $_SESSION['errors']);
			
			unset($_SESSION['errors']);
		}

		if(isset($_SESSION['phrase']))
		{
			$this->view->getEnvironment()->addGlobal('errors', ['captcha' => 'the captcha is wrong, please try again']);
			
			unset($_SESSION['phrase']);
		}
		
		$response = $handler->handle($request);
	
		return $response;
	}
}