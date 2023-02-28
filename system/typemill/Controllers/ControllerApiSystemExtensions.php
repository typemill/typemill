<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\StorageWrapper;
use Typemill\Models\License;
use Typemill\Static\Settings;

class ControllerApiSystemExtensions extends ControllerData
{
	public function activateExtension(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();

		# validate input
		$validate 			= new Validation();
		$vresult 			= $validate->activateExtension($params);

		if($vresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Something went wrong, the input is not valid.',
				'errors' 	=> $vresult
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if(!isset($this->settings[$params['type']][$params['name']]))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'The plugin or themes was not found.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$storage 			= new StorageWrapper('\Typemill\Models\Storage');

		if($params['checked'] == true)
		{
			$definitions 		= $storage->getYaml($params['type'] . DIRECTORY_SEPARATOR . $params['name'], $params['name'] . '.yaml');
			
			if(isset($definitions['license']) && in_array($definitions['license'], ['MAKER', 'BUSINESS']))
			{
				$license = new License();
				$licenseScope = $license->getLicenseScope($this->c->get('urlinfo'));
				if(!isset($licenseScope[$definitions['license']]))
				{
					$response->getBody()->write(json_encode([
						'message' 	=> 'We can not activate this plugin because you need a valid '. $definitions['license'] .'-license and your website must run under the domain of your license.',
					]));

					return $response->withHeader('Content-Type', 'application/json')->withStatus(400);					
				}
			}
		}

		$objectdata = [];
		$objectdata[$params['type']][$params['name']] = $this->settings[$params['type']][$params['name']];
		$objectdata[$params['type']][$params['name']]['active'] = $params['checked'];


		# store updated settings here
		$updatedSettings = Settings::updateSettings($objectdata);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved'
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}