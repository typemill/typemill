<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Extension;
use Typemill\Models\Media;
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
		$formdefinitions 	= $this->addDatasets($formdefinitions);

		# validate input
		$validator 			= new Validation();
		$validatedOutput 	= $validator->recursiveValidation($formdefinitions, $settingsinput);

		# Base project fields are mandatory when multi-project is enabled
		if(isset($validatedOutput['projects']) && $validatedOutput['projects'] !== 'standard')
		{
			if(empty($validatedOutput['baseprojectid']))
			{
				$validator->errors['baseprojectid'] = Translations::translate('Base Project ID is required for multi-project websites.');
			}
			if(empty($validatedOutput['baseprojectlabel']))
			{
				$validator->errors['baseprojectlabel'] = Translations::translate('Base Project Label is required for multi-project websites.');
			}
		}

		if(!empty($validator->errors))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validator->errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# if everything is fine, create customsizes for favicon
		if(isset($validatedOutput['favicon']) && $validatedOutput['favicon'] != '' && ($validatedOutput['favicon'] != $this->settings['favicon']))
		{
			$media = new Media();

			$sizes = [
	    				'16' => ['width' => 16, 'height' => 16], 
	    				'32' => ['width' => 32, 'height' => 32],
	    				'72' => ['width' => 72, 'height' => 72],
	    				'114' => ['width' => 114, 'height' => 114],
	    				'144' => ['width' => 144, 'height' => 144],
	    				'180' => ['width' => 180, 'height' => 180],
	    			];

			foreach ($sizes as $size)
			{
				$favicon = $media->createCustomSize($validatedOutput['favicon'], $size['width'], $size['height'], 'favicon');
			}
		}

		$securityFields = $settingsModel->findSecurityDefinitions($formdefinitions);

		if(!empty($securityFields))
		{
			$splitSettings = $settingsModel->extractSecuritySettings($validatedOutput, $securityFields);
			$validatedOutput = $splitSettings['settings'];

			if($splitSettings['securitySettings'] && !empty($splitSettings['securitySettings']))
			{
				$settingsModel->updateSecuritySettings($splitSettings['securitySettings']);
			}
		}

		$updatedSettings 	= $settingsModel->updateSettings($validatedOutput);

		$responseData = [
			'message' => Translations::translate('settings have been saved'),
		];

		# Security hint: proxy detection without trusted proxies trusts all proxies
		if(isset($validatedOutput['proxy']) && $validatedOutput['proxy'] && empty($validatedOutput['trustedproxies']))
		{
			$responseData['warning'] = Translations::translate('Proxy detection is active but no trusted proxies are configured. All proxies are trusted, which can be a security risk. Add trusted proxy IP addresses for stronger protection.');
		}

		$response->getBody()->write(json_encode($responseData));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}