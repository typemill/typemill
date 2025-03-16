<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Events\OnShortcodeFound;

class ControllerApiAuthorShortcode extends Controller
{
	public function getShortcodeData(Request $request, Response $response, $args)
	{
        $shortcodeData = $this->c->get('dispatcher')->dispatch(new OnShortcodeFound(['name' => 'registershortcode', 'data' => []]), 'onShortcodeFound')->getData();

        if(empty($shortcodeData['data']))
        {
			$response->getBody()->write(json_encode([
				'shortcodedata' => false			
			]));

			return $response->withHeader('Content-Type', 'application/json');
        }

		$response->getBody()->write(json_encode([
			'shortcodedata' => $shortcodeData['data']
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}
}