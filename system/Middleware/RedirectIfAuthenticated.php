<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RedirectIfAuthenticated
{		
	protected $router;
	
	public function __construct(RouterInterface $router, $settings)
	{
		$this->router = $router;
		$this->settings = $settings;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		$editor = (isset($this->settings['editor']) && $this->settings['editor'] == 'visual') ? 'visual' : 'raw';

		if(isset($_SESSION['login']))
		{
			$response = $response->withRedirect($this->router->pathFor('content.' . $editor));
		}
		
		return $next($request, $response);
	}
}