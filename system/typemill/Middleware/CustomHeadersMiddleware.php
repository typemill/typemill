<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class CustomHeadersMiddleware implements MiddlewareInterface
{
	protected $settings;
	
	public function __construct($settings)
	{
		$this->settings = $settings;
	}
	
	public function process(Request $request, RequestHandler $handler) :response
	{
		$scheme = $request->getUri()->getScheme();
		
		$response = $handler->handle($request);

		$response = $response->withoutHeader('Server');
		$response = $response->withHeader('X-Powered-By', 'Typemill');

		$headersOff = $this->settings['headersoff'] ?? false;
	
		if(!$headersOff)
		{
			$response = $response
				->withHeader('X-Content-Type-Options', 'nosniff')
				->withHeader('X-Frame-Options', 'SAMEORIGIN')
				->withHeader('X-XSS-Protection', '1;mode=block')
				->withHeader('Referrer-Policy', 'no-referrer-when-downgrade');

			if($scheme == 'https')
			{
				$response = $response->withHeader('Strict-Transport-Security', 'max-age=63072000');
			}
		}

		return $response;
	}
}