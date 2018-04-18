<?php

namespace Typemill\Controllers;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;

class ContentController extends Controller
{		
	/**
	* Show Content
	* 
	* @param obj $request the slim request object
	* @param obj $response the slim response object
	* @return obje $response with redirect to route
	*/
	
	public function showContent(Request $request, Response $response)
	{
		$this->render($response, 'content/content.twig', array('navigation' => true));
	}
}