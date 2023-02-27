<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\StorageWrapper;
use Typemill\Models\User;
use Typemill\Static\Settings;


class ControllerApiSystemThemes extends ControllerData
{
	public function updateTheme(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$themename 			= $params['theme'];
		$themeinput 		= $params['settings'];
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$formdefinitions 	= $storage->getYaml('themes' . DIRECTORY_SEPARATOR . $themename, $themename . '.yaml');
		$themedata 			= [];

		# validate input
		$validator 			= new Validation();
		$validatedOutput 	= $this->recursiveValidation($validator, $formdefinitions['forms']['fields'], $themeinput);

		if(!empty($this->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct tbe errors in form.',
				'errors' 	=> $this->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$themedata['themes'][$themename] = $validatedOutput;

		# store updated settings here
		$updatedSettings = Settings::updateSettings($themedata);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved',
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}