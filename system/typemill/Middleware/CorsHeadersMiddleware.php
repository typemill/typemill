<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class CorsHeadersMiddleware implements MiddlewareInterface
{
	protected $settings;

	protected $urlinfo;
	
	public function __construct($settings, $urlinfo)
	{
		$this->settings = $settings;

		$this->urlinfo = $urlinfo;
	}
	
	public function process(Request $request, RequestHandler $handler) :response
	{		
		# add the custom headers to the response after everything is processed
		$response = $handler->handle($request);

		###################
		#   CORS HEADER   #
		###################

    	$origin 		= $request->getHeaderLine('Origin');
		$corsdomains 	= isset($this->settings['corsdomains']) ? trim($this->settings['corsdomains']) : false;
		$whitelist 		= [];

		if($corsdomains && $corsdomains != '')
		{
			$corsdomains = explode(",", $corsdomains);
			foreach($corsdomains as $domain)
			{
				$domain = trim($domain);
				if($domain != '')
				{
					$whitelist[] = $domain;
				}
			}
		}

		if(!$origin OR $origin == '' OR !isset($whitelist[$origin]))
		{
			# set current website as default origin and block all cross origin calls
			$origin = $this->urlinfo['baseurl'];
		}

		$response =  $response->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');

		return $response;
	}
}