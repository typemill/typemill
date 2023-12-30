<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class CspHeadersMiddleware implements MiddlewareInterface
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
		$scheme = $request->getUri()->getScheme();
		
		# add the custom headers to the response after everything is processed
		$response = $handler->handle($request);

		###################
		#   CSP HEADER   #
		###################

		$cspdomains = isset($this->settings['cspdomains']) ? trim($this->settings['cspdomains']) : false;
		$defaultsrc = "default-src 'unsafe-inline' 'unsafe-eval' 'self'";

		if($cspdomains && $cspdomains != '')
		{
			$cspdomains = explode(",", $cspdomains);
			foreach($cspdomains as $cspdomain)
			{
				$cspdomain = trim($cspdomain);
				if($cspdomain != '')
				{
					$defaultsrc .= ' ' . $cspdomain;
				}
			}
		}

	    # dispatch to get from plugins
		# get yaml from current theme

	    # Define the Content Security Policy header
	    $cspHeader =  $defaultsrc . ";"; // Default source is restricted to 'self'
#	    $cspHeader .= "frame-src 'self' https://www.youtube.com;"; // Allowing embedding YouTube videos
# 		$cspHeader .= "img-src *";
# 		$cspHeader .= "media-src *";
# 		$cspHeader .= "script-src *";
# 		$cspHeader .= "style-src *";
# 		$cspHeader .= "object-src 'none'";

	    # Add the Content Security Policy header to the response
	    $response = $response->withHeader('Content-Security-Policy', $cspHeader);

		return $response;
	}
}