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
		if(isset($_SESSION) && isset($_SESSION['old']))
		{
			$this->view->getEnvironment()->addGlobal('old', $_SESSION['old']);
		}

		$response = $handler->handle($request);

		# unset old values after the request is processed. This keeps old values also if there is a redirect to another page and before the page is rendered but removes the values on page refresh.
		if(isset($_SESSION))
		{
			unset($_SESSION['old']);

 			if(!empty($request->getParsedBody()))
 			{
			    $oldinput = $request->getParsedBody();

			    if(is_array($oldinput))
			    {
				    foreach($oldinput as $key => $value)
				    {
				        if (stripos($key, 'pass') !== false)
				        {
				            unset($oldinput[$key]);
				        }
			    	}
			    }

				$_SESSION['old'] = $oldinput;
 			}
		}

		return $response;
	}
}