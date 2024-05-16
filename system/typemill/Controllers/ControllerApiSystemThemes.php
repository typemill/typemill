<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Extension;
use Typemill\Models\Settings;
use Typemill\Static\Translations;
use Typemill\Static\Slug;

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

	public function updateReadymade(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$themename 			= $params['theme'] ?? false;
		$themeinput 		= $params['settings'] ?? false;
		$readymadetitle 	= $params['readymadetitle'] ?? false;
		$readymadedesc 		= $params['readymadedesc'] ?? false;

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

		$validator = $validator->returnValidator(['themename' => $themename, 'title' => $readymadetitle, 'description' => $readymadedesc]);
		$validator->rule('required', ['themename', 'title', 'description']);
		$validator->rule('lengthBetween', 'description', 3, 100)->message("Length between 3 - 100");
		$validator->rule('noHTML', 'description')->message(" contains HTML");
		$validator->rule('regex', 'themename', '/^[a-zA-Z0-9\-]{3,40}$/')->message("only a-zA-Z0-9 with 3 - 40 chars allowed");
		$validator->rule('regex', 'title', '/^[a-zA-Z0-9\-\_ ]{3,20}$/')->message("only a-zA-Z0-9 with 3 - 20 chars allowed");
		
		if(!$validator->validate())
		{
			$message = 'There was an error, please try again';
			$errors = $validator->errors();
			$firstKey = array_key_first($errors);
			if(isset($errors[$firstKey][0]))
			{
				$message = $firstKey . ': ' . $errors[$firstKey][0];
			}

			$response->getBody()->write(json_encode([
				'message' 	=> $message
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$readymadeSlug 		= Slug::createSlug($readymadetitle);

		$readymade 			= [
			$readymadeSlug => [
				'name' 			=> $readymadetitle,
				'description' 	=> $readymadedesc,
				'delete'		=> true,
				'settings' 		=> $validatedOutput
			]
		];

		$extension->storeThemeReadymade($themename, $readymadeSlug, $readymade);

		$response->getBody()->write(json_encode([
			'message' 	=> Translations::translate('Readymade has been saved'),
			'readymade' => $readymade,
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function deleteReadymade(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$themename 			= $params['theme'] ?? false;
		$readymadeslug 		= $params['readymadeslug'] ?? false;

		$validation 		= new Validation();
		$validator 			= $validation->returnValidator($params);
		$validator->rule('required', ['theme', 'readymadeslug']);
		$validator->rule('regex', 'theme', '/^[a-zA-Z0-9\-]{3,40}$/')->message("only a-zA-Z0-9 with 3 - 40 chars allowed");
		$validator->rule('regex', 'readymadeslug', '/^[a-zA-Z0-9\-\_ ]{3,20}$/')->message("only a-zA-Z0-9 with 3 - 20 chars allowed");

		if(!$validator->validate())
		{
			$message = 'There was an error, please try again';
			$errors = $validator->errors();
			$firstKey = array_key_first($errors);
			if(isset($errors[$firstKey][0]))
			{
				$message = $firstKey . ': ' . $errors[$firstKey][0];
			}

			$response->getBody()->write(json_encode([
				'message' 	=> $message
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$extension 			= new Extension();
		$result 			= $extension->deleteThemeReadymade($themename, $readymadeslug);

		if($result !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We could not delete the readymade.'),
				'errors' 	=> $result
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' 	=> Translations::translate('readymade has been deleted')
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}
