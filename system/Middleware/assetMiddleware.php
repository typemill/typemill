<?php

namespace Typemill\Middleware;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;

class assetMiddleware
{
    protected $view;
    protected $c;
    
    public function __construct($container)
    {
        # $this->view = $view;
        $this->container = $container;
    }
    
    public function __invoke(Request $request, Response $response, $next)
    {

    	# get the uri after proxy detection
        $uri 	= $request->getUri()->withUserInfo('');

		# update the asset object in the container (for plugins) with the new url
		$this->container->assets->setBaseUrl($uri->getBaseUrl());

        # add the asset object to twig-frontend for themes
        $this->container['view']->getEnvironment()->addGlobal('assets', $this->container['assets']);
        
        # use {{ base_url() }} in twig templates
		$this->container['view']['base_url']	 	= $uri->getBaseUrl();
		$this->container['view']['current_url'] 	= $uri->getPath();

	    $response = $next($request, $response);
	    
	    return $response;
    }
}