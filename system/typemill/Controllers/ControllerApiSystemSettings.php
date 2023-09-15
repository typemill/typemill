<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Extension;
use Typemill\Models\User;
use Typemill\Models\Settings;
use Typemill\Static\Translations;

class ControllerApiSystemSettings extends Controller
{
	public function getSettings(Request $request, Response $response)
	{
		$response->getBody()->write(json_encode([
			'settings'	=> $this->settings
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function updateSettings(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$settingsinput 		= $params['settings'];
		$settingsModel 		= new Settings();

		$formdefinitions 	= $settingsModel->getSettingsDefinitions();

		# validate input
		$validator 			= new Validation();
		$validatedOutput 	= $validator->recursiveValidation($formdefinitions, $settingsinput);

		if(!empty($validator->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct errors in form.'),
				'errors' 	=> $validator->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# store updated settings here
		$updatedSettings 	= $settingsModel->updateSettings($validatedOutput);

		$response->getBody()->write(json_encode([
			'message' => Translations::translate('settings have been saved'),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}