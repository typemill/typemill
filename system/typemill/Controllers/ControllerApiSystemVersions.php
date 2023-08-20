<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;

class ControllerApiSystemVersions extends Controller
{
	public function checkVersions(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();

		# validate input
		$validate 			= new Validation();
		$vresult 			= $validate->checkVersions($params);
		if($vresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'The version check failed because of invalid parameters.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$type 				= $params['type'];
		$data 				= $params['data'];
		$url 				= 'https://typemill.net/api/v1/checkversion';

		if($type == 'plugins')
		{
			$pluginList = '';
			foreach($data as $name => $plugin)
			{
				$pluginList .= $name . ',';
			}
			
			$url = 'https://plugins.typemill.net/api/v1/getplugins?plugins=' . urlencode($pluginList);
		}
		if($type == 'themes')
		{
			$themeList = '';
			foreach($data as $name => $theme)
			{
				$themeList .= $name . ',';
			}
			
			$url = 'https://themes.typemill.net/api/v1/getthemes?themes=' . urlencode($themeList);
		}

		$opts = array(
		  'http'=>array(
		    'method'=>"GET",
			'ignore_errors' => true,
		    'timeout' => 5,
		    'header'=>"Referer: http://typemill-version2.net"
		  )
		);

		$context 			= stream_context_create($opts);
		$versions 			= file_get_contents($url, false, $context);
		$versions 			= json_decode($versions, true);
		$updateVersions 	= [];

		if($type == 'system')
		{
				$latestVersion 		= $versions['system']['typemill'] ?? false;
				$installedVersion 	= $data ?? false;
				if($latestVersion && $installedVersion && version_compare($latestVersion, $installedVersion) <= 0)
				{
					$updateVersions['system'] = $latestVersion; 
				}
		}
		elseif(isset($versions[$type]))
		{
			foreach($versions[$type] as $name => $details)
			{
				$latestVersion 		= $details['version'] ?? false;
				$installedVersion 	= $data[$name] ?? false;
				if($latestVersion && $installedVersion && version_compare($latestVersion, $installedVersion) <= 0)
				{
					$updateVersions[$name] = $details; 
				}
			}
		}

		$response->getBody()->write(json_encode([
			$type => $updateVersions
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}