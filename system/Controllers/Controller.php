<?php

namespace Typemill\Controllers;

/* Use the slim-container */
use Interop\Container\ContainerInterface;
use Typemill\Events\RenderPageEvent;

abstract class Controller
{
	protected $c;

	public function __construct(ContainerInterface $c)
	{
		$this->c = $c;
	}
	
	protected function render($response, $route, $data)
	{
		$data = $this->c->dispatcher->dispatch('onPageRendered', new RenderPageEvent($data))->getData();
		
		return $this->c->view->render($response, $route, $data);
	}
	
	protected function render404($response, $data = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/404.twig', $data);
	}
}