<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Navigation;

class ControllerApiGlobals extends Controller
{
	public function getSystemNavi(Request $request, Response $response)
	{
		$navigation 		= new Navigation();
		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo')
								);

		# won't work because api has no session, instead you have to pass user
		$response->getBody()->write(json_encode([
			'systemnavi' => $systemNavigation
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getMainNavi(Request $request, Response $response)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$response->getBody()->write(json_encode([
			'mainnavi' => $mainNavigation
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getTranslations(Request $request, Response $response)
	{		
		$response->getBody()->write(json_encode([
			'translations' => $this->c->get('translations'),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}