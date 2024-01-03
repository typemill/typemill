<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class CspHeadersMiddleware implements MiddlewareInterface
{
	protected $settings;

	protected $cspFromPlugins;

	protected $cspFromTheme;

	public function __construct($settings, $cspFromPlugins, $cspFromTheme)
	{
		$this->settings = $settings;

		$this->cspFromPlugins = $cspFromPlugins;

		$this->cspFromTheme = $cspFromTheme;
	}
	
	public function process(Request $request, RequestHandler $handler) :response
	{		
		# add the custom headers to the response after everything is processed
		$response = $handler->handle($request);

		$whitelist 	= ["'unsafe-inline'", "'unsafe-eval'", "'self'", "*.youtube-nocookie.com", "*.youtube.com"];

		$cspdomains = isset($this->settings['cspdomains']) ? trim($this->settings['cspdomains']) : false;

		if($cspdomains && $cspdomains != '')
		{
			$cspdomains = explode(",", $cspdomains);
			foreach($cspdomains as $cspdomain)
			{
				$cspdomain = trim($cspdomain);
				if($cspdomain != '')
				{
					$whitelist[] = $cspdomain;
				}
			}
		}

	    # add csp from plugins
		if($this->cspFromPlugins && is_array($this->cspFromPlugins) && !empty($this->cspFromPlugins))
		{
			$whitelist = array_merge($whitelist, $this->cspFromPlugins);
		}

		# add csp from current theme
		if($this->cspFromTheme && is_array($this->cspFromTheme) && !empty($this->cspFromTheme))
		{
			$whitelist = array_merge($whitelist, $this->cspFromTheme);
		}

		$whitelist = array_unique($whitelist);
		$whitelist = implode(' ', $whitelist);

	    # Define the Content Security Policy header
	    $cspHeader =  "default-src " . $whitelist . ";";
	    
	    # Add the Content Security Policy header to the response
	    $response = $response->withHeader('Content-Security-Policy', $cspHeader);

		return $response;
	}
}