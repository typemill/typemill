<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Extension;
use Typemill\Models\Settings;
use Typemill\Static\Translations;

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
		$validatedOutput 	= $validator->recursiveValidation($formdefinitions, $themeinput);
		if(!empty($validator->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validator->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# delete themecss
		unset($validatedOutput['customcss']);

		# store updated settings here
		$settings 			= new Settings();
		$updatedSettings 	= $settings->updateSettings($validatedOutput, 'themes', $themename);

		$response->getBody()->write(json_encode([
			'message' => Translations::translate('settings have been saved')
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function updateThemeCss(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$themename 			= $params['theme'];
		$themecss 			= $params['css'];

		# validate css input
		$themecss 			= strip_tags($themecss);

		# store updated css
		$settings 			= new Settings();
		$updatedSettings 	= $settings->updateThemeCss($themename, $themecss);

		$response->getBody()->write(json_encode([
			'message' => Translations::translate('settings have been saved'),
			'code' => $updatedSettings
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}	
}
