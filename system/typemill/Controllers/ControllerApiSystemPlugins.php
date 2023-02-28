<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\StorageWrapper;
use Typemill\Models\User;
use Typemill\Static\Settings;

class ControllerApiSystemPlugins extends ControllerData
{
	public function updatePlugin(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$pluginname 		= $params['plugin'];
		$plugininput 		= $params['settings'];
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$formdefinitions 	= $storage->getYaml('plugins' . DIRECTORY_SEPARATOR . $pluginname, $pluginname . '.yaml');
		$plugindata 		= [];

		# validate input
		$validator 			= new Validation();
		$validatedOutput 	= $this->recursiveValidation($validator, $formdefinitions['forms']['fields'], $plugininput);

		if(!empty($this->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct tbe errors in form.',
				'errors' 	=> $this->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# keep the active setting
		$validatedOutput['active'] = false;
		if(isset($plugininput['active']) && $plugininput['active'] == true)
		{
			$validatedOutput['active'] = true;
		}

		$plugindata['plugins'][$pluginname] = $validatedOutput;

		# store updated settings here
		$updatedSettings = Settings::updateSettings($plugindata);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved'
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}