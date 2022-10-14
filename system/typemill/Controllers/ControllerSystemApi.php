<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ControllerSystemApi extends ControllerData
{
	public function getSettings(Request $request, Response $response)
	{
		$response->getBody()->write(json_encode([
			'settings'	=> $this->settings
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getSystemNavi(Request $request, Response $response)
	{
		# won't work because api has no session, instead you have to pass user
		$response->getBody()->write(json_encode([
			'systemnavi' => $this->getSystemNavigation('member'),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getMainNavi(Request $request, Response $response)
	{
		$response->getBody()->write(json_encode([
			'mainnavi' => $this->getMainNavigation('member'),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}