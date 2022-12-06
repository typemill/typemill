<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Yaml;
use Typemill\Models\User;

class ControllerApiSystemThemes extends ControllerData
{
	public function updateTheme(Request $request, Response $response)
	{
		# minimum permission are admin rights
		if(!$this->c->get('acl')->isAllowed($request->getAttribute('userrole'), 'system', 'update'))
		{
			$response->getBody()->write(json_encode([
				'message' => 'You are not allowed to update settings.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
		}

		$params 			= $request->getParsedBody();
		$themename 			= $params['theme'];
		$themeinput 		= $params['settings'];
		$yaml 				= new Yaml('\Typemill\Models\Storage');
		$formdefinitions 	= $yaml->getYaml('themes' . DIRECTORY_SEPARATOR . $themename, $themename . '.yaml');

		# validate input
		$validator 			= new Validation();
		$this->recursiveValidation($formdefinitions['forms']['fields'], $themeinput, $validator, $themeOrPlugin = 'themes', $name = $themename);

		if(!empty($this->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct tbe errors in form.',
				'errors' 	=> $this->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# store updated settings here
		$yaml->updateYaml('settings', 'settings.yaml', $this->settings);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved',
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}