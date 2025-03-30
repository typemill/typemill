<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\License;
use Typemill\Models\Settings;
use Typemill\Models\User;
use Typemill\Models\ApiCalls;
use Typemill\Static\Translations;

class ControllerApiKixote extends Controller
{
	private $error = false;

	public function getKixoteSettings(Request $request, Response $response)
	{
		$settingsModel = new Settings();
		$kixoteSettings = $settingsModel->getKixoteSettings();

		if(!$kixoteSettings)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'could not load kixote settings.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# send to Kixote
		$response->getBody()->write(json_encode([
			'settings' => $kixoteSettings
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

	}

	public function updateKixoteSettings(Request $request, Response $response)
	{
		$params 			= $request->getParsedBody();
		$kixoteSettings 	= $params['kixotesettings'] ?? false;
		$validate			= new Validation();
		$cleanSettings 		= [];

		if(isset($kixoteSettings['promptlist']))
		{
			$promptErrors = false;
			foreach($kixoteSettings['promptlist'] as $name => $values)
			{
				$validInput 		= $validate->kixotePrompt($values);
				if($validInput !== true)
				{
					$promptErrors = true;
					$kixoteSettings['promptlist'][$name]['errors'] = $validInput;
				}
				else
				{
					$cleanSettings['promptlist'][$name] = $values;
					unset($kixoteSettings['promptlist'][$name]['errors']);
				}
			}

			if($promptErrors)
			{
				$response->getBody()->write(json_encode([
					'message' 			=> 'please correct the errors in the form',
					'kixotesettings' 	=> $kixoteSettings
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
			}

		}

		$settingsModel = new Settings();
		$result = $settingsModel->updateKixoteSettings($cleanSettings);

		if(!$result)
		{
			# restore the current kixote-settings
			$kixoteSettings = $settingsModel->getKixoteSettings();

			$response->getBody()->write(json_encode([
				'message' 	=> 'error while saving settings.',
				'kixotesettings' => $kixoteSettings
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# send to Kixote
		$response->getBody()->write(json_encode([
			'kixotesettings' => $kixoteSettings
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	# initial token statistics
	public function getTokenStats(Request $request, Response $response): Response
	{
		$aiservice 		= false;
		$tokenstats 	= 0;
		$useragreement  = false;
		$user 			= new User();
		$username 		= $request->getAttribute('c_username');

		if(!$user->setUser($username))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We did not find the a user.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if(isset($this->settings['aiservice']) && $this->settings['aiservice'] !== 'none')
		{
			$aiservice = $this->settings['aiservice'];
		}

		if($aiservice)
		{
			$userdata 		= $user->getUserData();
			if(isset($userdata['aiservices']) && in_array($aiservice, $userdata['aiservices']))
			{
				$useragreement = true;
			}
		}

		# get toke stats for AI service
		if($aiservice && $useragreement)
		{
			switch ($aiservice)
			{
				case 'chatgpt':
					$tokenstats = [
						'url' => 'https://platform.openai.com/settings/organization/billing/overview',
						'label' => 'ChatGPT Billing'
					];
					break;
				
				default:
					$tokenstats = 0;
					break;
			}
		}

		if($tokenstats === false)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Could not get tokenstats.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

	    $response->getBody()->write(json_encode([
	        'message' 		=> 'Success',
	        'aiservice' 	=> $aiservice,
	        'useragreement' => $useragreement,
	        'tokenstats' 	=> $tokenstats
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	# initial token statistics
	public function agreeToAiService(Request $request, Response $response): Response
	{
		$aiservice 		= false;
		$user 			= new User();
		$username 		= $request->getAttribute('c_username');

		if(!$user->setUserWithPassword($username))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We did not find the a user or usermail.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if(isset($this->settings['aiservice']) && $this->settings['aiservice'] !== 'none')
		{
			$aiservice = $this->settings['aiservice'];
		}
		else
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('No valid ai service has been selected.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$agreements = $user->getValue('aiservices');

		if(!$agreements)
		{
			$agreements = [$aiservice];
		}
		elseif(!isset($agreements[$aiservice]))
		{
			$agreements[] = $aiservice;
		}

		$user->setValue('aiservices', $agreements);		
		if($user->updateUser() !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We could not update your user settings, please try again or agree to ' . $aiservice . ' in your user profile.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

	    $response->getBody()->write(json_encode([
	        'message' 		=> 'Success'
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	private function getKixoteJWT(Request $request, Response $response)
	{
		# this will authenticate from service.typemill.net (e.g. for template service)
		$license = new License();
		$jwt = $license->getToken();
		if($jwt)
		{
			$this->error = $license->getMessage();
			return false;
		}

		# if no agb-confirmation
		$confirm = $settings['kixote_confirm'] ?? false;
		if(!$confirm)
		{
			$this->error = 'Please read and accept the AGB before you start with our service.';
			return false; 
		}

		return $jwt;
	}

	public function promptKixote(Request $request, Response $response)
	{
		$jwt = $this->getKixoteJWT();
		if(!$jwt)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $this->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$params 			= $request->getParsedBody();
		
		$params['name'] 	= ''; # will trigger some cool stuff in kixote
		$params['prompt'] 	= ''; # the prompt itself
		$params['article'] 	= ''; # the current article
		$params['tone'] 	= ''; # the tone

		if(!isset($params['prompt']) OR !is_array($params['article']))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Prompt or article missing.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# validate input
		$validate 			= new Validation();
		$validationresult	= $validate->newLicense($params['license']);
		if($validationresult !== true)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Please correct your input.'),
				'errors' 	=> $validate->returnFirstValidationErrors($validationresult)
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# send to Kixote
		$response->getBody()->write(json_encode([
			'message' => Translations::translate('Licence has been stored'),
			'licensedata' => $licensedata
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function promptChatGPT(Request $request, Response $response): Response
	{
		# check if user has accepted 

	    $params = $request->getParsedBody();

	    $params['name'] = $params['name'] ?? '';
	    $params['prompt'] = $params['prompt'] ?? '';
	    $params['article'] = $params['article'] ?? '';
	    $params['tone'] = $params['tone'] ?? '';

		$settingsModel = new Settings();
	    $model = $this->settings['chatgptModel'] ?? false;
	    $apikey = $settingsModel->getSecret('chatgptKey');

	    if (empty($params['prompt']) || !is_string($params['prompt']))
	    {
	        $response->getBody()->write(json_encode([
	            'message' => 'Prompt is missing or invalid.'
	        ]));
	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
	    }

	    if (empty($params['article']) || !is_string($params['article']))
	    {
	        $response->getBody()->write(json_encode([
	            'message' => 'Article is missing or invalid.'
	        ]));
	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
	    }

	    if (!$model || !$apikey)
	    {
	        $response->getBody()->write(json_encode([
	            'message' => 'Model or api key for chatgpt is missing, please add it in the system settings.'
	        ]));
	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);	
	    }

	    $url = 'https://api.openai.com/v1/chat/completions';
	    $authHeader = "Authorization: Bearer $apikey";

	    $postdata = [
	        'model' => $model,
	        'messages' => [
	            [
	                'role' => 'system',
	                'content' => 'You are a content editor and writing assistant. If the user prompt does not explicitly specify otherwise, apply the prompt to the provided article and return only the updated article in Markdown syntax, without any extra comments or explanations. If you find the tag <focus></focus>, modify only the content inside these tags and leave everything else unchanged. Always return the full article.'
	            ],
	            [
	                'role' => 'user',
	                'content' => $params['prompt'] . "\n" . $params['article']
	            ],
	        ],
	        'temperature' => 0.7,
	        'max_tokens' => 2000,
	    ];

	    $apiservice = new ApiCalls();
	    $apiResponse = $apiservice->makePostCall($url, $postdata, $authHeader);

	    if (!$apiResponse)
	    {
	        $response->getBody()->write(json_encode([
	            'message' 	=> 'Failed to communicate with ChatGPT',
	            'error'		=> $apiservice->getError()
	        ]));

	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
	    }

	    $data = json_decode($apiResponse, true);
	    $response->getBody()->write(json_encode([
	        'message' => 'Success',
	        'data' => $data,
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}