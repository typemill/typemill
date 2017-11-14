<?php

namespace Typemill\Controllers;

/* Use the slim-container */
use Interop\Container\ContainerInterface;

abstract class Controller
{
	protected $c;

	public function __construct(ContainerInterface $c)
	{
		$this->c = $c;
	}
	
	protected function render404($response, $content = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/404.twig', $content);
	}
}

?>