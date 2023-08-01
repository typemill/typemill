<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Extension;
use Typemill\Models\Settings;

class ControllerApiSystemThemes extends Controller
{
	public function updateTheme(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$themename 			= $params['theme'];
		$themeinput 		= $params['settings'];

		$extension 			= new Extension();
		$formdefinitions 	= $extension->getThemeDefinition($themename);
		$formdefinitions 	= $this->addDatasets($formdefinitions['forms']['fields']);		
		$themedata 			= [];

		# validate input
		$validator 			= new Validation();
		$validatedOutput 	= $this->recursiveValidation($validator, $formdefinitions, $themeinput);
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
		$settings 			= new Settings();
		$updatedSettings 	= $settings->updateSettings($themedata);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved',
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}