<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\StorageWrapper;
use Typemill\Models\User;
use Typemill\Static\Settings;


# how to translate results in API call ???
# we should translate in backend instead of twig or vue

class ControllerApiSystemSettings extends ControllerData
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
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$formdefinitions 	= $storage->getYaml('system/typemill/settings', 'system.yaml');

		# validate input
		$validator 			= new Validation();
		$validatedOutput 	= $this->recursiveValidation($validator, $formdefinitions, $settingsinput);

		if(!empty($this->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct errors in form.',
				'errors' 	=> $this->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# store updated settings here
		$updatedSettings = Settings::updateSettings($validatedOutput);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved',
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}