<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\License;
use Typemill\Static\Translations;

class ControllerApiSystemVersions extends Controller
{
	public function checkVersions(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$error 				= false;

		# validate input
		$validate 			= new Validation();
		$vresult 			= $validate->checkVersions($params);
		if($vresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('The version check failed because of invalid parameters.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$type 				= $params['type'];
		$data 				= $params['data'];
		$url 				= 'https://typemill.net/api/v1/checkversion';
#		$url2 				= 'http://localhost/typemillPlugins/api/v1/checkversion';

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

		$license = new License();
		$authstring = $license->getPublicKeyPem();
		if(!$authstring)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please check if there is a readable file public_key.pem in your settings folder.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}
		$authstring = hash('sha256', substr($authstring, 0, 50));

	    if (function_exists('curl_version'))
	    {
	        $curl = curl_init();

	        curl_setopt($curl, CURLOPT_URL, $url);
	        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
	        curl_setopt($curl, CURLOPT_HTTPHEADER, [
	            "Accept: application/json",
	            "Authorization: $authstring",
	            "Connection: close"
	        ]);

	        $curl_response = curl_exec($curl);

	        if (curl_errno($curl))
	        {
	            $error = curl_error($curl);
	        }
	        else
	        {
		        $versions = json_decode($curl_response, true);
	        }

	        curl_close($curl);
		}
		else
		{
			$opts = array(
			  'http' => array(
			    'method'		=>"GET",
				'ignore_errors' => true,
			    'timeout' 		=> 5,
	    		'header'		=>
									"Accept: application/json\r\n" .
									"Authorization: $authstring\r\n" .
									"Connection: close\r\n",
			  )
			);

			$context 			= stream_context_create($opts);
			$versions 			= file_get_contents($url, false, $context);
	        if ($versions === false)
	        {
	            $error 			= "file_get_contents error: failed to fetch data from $url";
	        }
	        else
	        {
				$versions 		= json_decode($versions, true);
	        }
		}

		if($error)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);			
		}

		$updateVersions 	= [];

		if($type == 'system')
		{
				$latestVersion 		= $versions['system']['typemill'] ?? false;
				$installedVersion 	= $data ?? false;
				if($latestVersion && $installedVersion && version_compare($latestVersion, $installedVersion) > 0)
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
				if($latestVersion && $installedVersion && version_compare($latestVersion, $installedVersion) > 0)
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