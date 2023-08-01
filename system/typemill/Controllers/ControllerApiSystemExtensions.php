<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\License;
use Typemill\Models\Extension;
use Typemill\Models\Settings;

class ControllerApiSystemExtensions extends Controller
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

		if($params['checked'] == true)
		{
			$extension			= new Extension();

			if($params['type'] == 'plugins')
			{
				$definitions = $extension->getPluginDefinition($params['name']);
			}
			elseif($params['type'] == 'themes')
			{
				$definitions = $extension->getThemeDefinition($params['name']);
			}

			if(isset($definitions['license']) && in_array($definitions['license'], ['MAKER', 'BUSINESS']))
			{
				$license 		= new License();
				$licenseScope 	= $license->getLicenseScope($this->c->get('urlinfo'));
				
				if(!isset($licenseScope[$definitions['license']]))
				{
					$response->getBody()->write(json_encode([
						'message' => 'Activation failed because you need a valid '. $definitions['license'] .'-license for this and your website must run under the domain of your license.',
					]));

					return $response->withHeader('Content-Type', 'application/json')->withStatus(400);					
				}
			}
		}

		$objectdata = [];
		if($params['type'] == 'plugins')
		{
			$objectdata['plugins'][$params['name']] = $this->settings[$params['type']][$params['name']];
			$objectdata['plugins'][$params['name']]['active'] = $params['checked'];
		}
		elseif($params['type'] == 'themes')
		{
			$objectdata['theme'] = $params['name'];
		}

		# store updated settings here
		$settings 			= new Settings();
		$updatedSettings 	= $settings->updateSettings($objectdata);

		$response->getBody()->write(json_encode([
			'message' => 'settings have been saved'
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}