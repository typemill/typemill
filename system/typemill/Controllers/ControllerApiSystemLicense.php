<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\License;

class ControllerApiSystemLicense extends ControllerData
{
	public function createLicense(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		
		if(!isset($params['license']) OR !is_array($params['license']))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'License data missing.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# validate input
		$validate 			= new Validation();
		$validationresult	= $validate->newLicense($params['license']);
		if($validationresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Please correct errors in form.',
				'errors' 	=> $validate->returnFirstValidationErrors($validationresult)
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$license = new License();

		$licensedata = $license->activateLicense($params['license']);

		if(!$licensedata)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $license->getMessage()
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'Licence has been stored',
			'licensedata' => $license->getLicenseData($this->c->get('urlinfo'))
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}