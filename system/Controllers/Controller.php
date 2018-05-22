<?php

namespace Typemill\Controllers;

/* Use the slim-container */
use Interop\Container\ContainerInterface;
use Typemill\Events\OnPageReady;

abstract class Controller
{
	protected $c;

	public function __construct(ContainerInterface $c)
	{
		$this->c = $c;
	}
	
	protected function render($response, $route, $data)
	{
		$data = $this->c->dispatcher->dispatch('onPageReady', new OnPageReady($data))->getData();

		unset($_SESSION['old']);
		
		$response = $response->withAddedHeader('X-Content-Type-Options', 'nosniff');
		$response = $response->withAddedHeader('X-Frame-Options', 'SAMEORIGIN');
		$response = $response->withAddedHeader('X-XSS-Protection', '1;mode=block');
		
		return $this->c->view->render($response, $route, $data);
	}
	
	protected function render404($response, $data = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/404.twig', $data);
	}
}