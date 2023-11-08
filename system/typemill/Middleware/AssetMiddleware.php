<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
#use Slim\Psr7\Response;

class AssetMiddleware implements MiddlewareInterface
{
    protected $assets;

    protected $view;
    
    public function __construct($assets, $view)
    {
        $this->assets = $assets;

        $this->view = $view;
    }
    
	public function process(Request $request, RequestHandler $handler) :response
    {
    	# get url from request

		# update the asset object in the container (for plugins) with the new url
#		$this->container->assets->setBaseUrl($uri->getBaseUrl());

        # add the asset object to twig-frontend for themes
		$this->view->getEnvironment()->addGlobal('assets', $this->assets);

        # use {{ base_url() }} in twig templates
#		$this->container['view']['base_url']	 	= $uri->getBaseUrl();
#		$this->container['view']['current_url'] 	= $uri->getPath();

        $response = $handler->handle($request);
    
        return $response;
    }
}