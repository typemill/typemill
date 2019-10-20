<?php

namespace Typemill\Middleware;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;

class OldInputMiddleware
{
	protected $view;
	
	public function __construct(Twig $view)
	{
		$this->view = $view;
	}
	
	public function __invoke(Request $request, Response $response, $next)
	{
		if(isset($_SESSION['old']))
		{
			$this->view->getEnvironment()->addGlobal('old', $_SESSION['old']);
		}
		if(!empty($request->getParams()))
		{
			$_SESSION['old'] = $request->getParams();
		}
		
		$response = $next($request, $response);
		return $response;
	}
}