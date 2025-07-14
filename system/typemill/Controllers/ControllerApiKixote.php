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

	private function getSystemMessage()
	{
		$system = 'You are a content editor and writing assistant.'
		          . ' If the user prompt does not explicitly specify otherwise,'
		          . ' apply the prompt to the provided article inside the <article></article> tag and return only the updated article in Markdown syntax,'
		          . ' without any extra comments or explanations.'
		          . ' If you find the tag <focus></focus>,'
		          . ' modify only the content inside these tags and leave everything else unchanged.' 
		          . ' Always return the full article.';

		return $system;		     
	}

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
			'kixotesettings' => $kixoteSettings
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

		# get token stats for AI service
		if($aiservice && $useragreement)
		{
			switch ($aiservice)
			{
				case 'chatgpt':
					$tokenstats = [
						'service' 	=> 'ChatGPT',
						'url' 		=> 'https://platform.openai.com/settings/organization/billing/overview',
					];
					break;

				case 'claude':
					$tokenstats = [
						'service' 	=> 'Claude',
						'url' 		=> 'https://console.anthropic.com/usage',
					];
					break;
				
				default:
					$tokenstats = [
						'service' 	=> 'Kixote',
						'token' 	=> 0
					];
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

	public function prompt(Request $request, Response $response)
	{
	    $params = $request->getParsedBody();

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

	    $promptname = $params['name'] ?? '';
	    $prompt 	= $params['prompt'] ?? '';
	    $article 	= $params['article'] ?? '';
	    $example 	= $params['example'] ?? false;
		 
	    if($example && $example != "")
	    {
		    $validation = new Validation();
		    $v = $validation->returnValidator(['content' => $example]);
		    $v->rule('markdownSecure', 'content');
			if(!$v->validate())
			{
				$example = false;
			}
			else
			{
				# Rough estimate: 1 token ≈ 4 characters
				$allContent = $prompt . $article . $example;
				$length = strlen($allContent);
				$maxlength = 8000 * 4;
				if ($length > $maxlength)
				{
				    $overLimit = $length - $maxlength;
				    $keep = strlen($example) - $overLimit;
				    if($keep > 0)
				    {
						$example = substr($example, 0, $keep);
				    }
				    else
				    {
				    	$example = false;
				    }
				}
			}
	    }

	    $aiservice 	= $this->settings['aiservice'] ?? false;
	    if(!$aiservice)
	    {
	        $response->getBody()->write(json_encode([
	            'message' => 'No ai service is selected.'
	        ]));
	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);	    	
	    }

	    switch ($aiservice) {
	    	case 'chatgpt':
	    		$answer = $this->promptChatGPT($promptname, $prompt, $article, $example);
	    		break;
	    	
	    	case 'claude':
	    		$answer = $this->promptClaude($promptname, $prompt, $article, $example);
	    		break;

	    	default:
	    		$answer = false;
	    		break;
	    }

	    if(!isset($answer) or !$answer)
	    {
	        $response->getBody()->write(json_encode([
	            'message' => $this->error
	        ]));

	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
	    }

	    $response->getBody()->write(json_encode([
	        'message' 	=> 'Success',
	        'answer' 	=> $answer,
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function promptChatGPT($promptname, $prompt, $article, $example)
	{
		# check if user has accepted 

		$settingsModel 	= new Settings();
	    $model 			= $this->settings['chatgptModel'] ?? false;
	    $apikey 		= $settingsModel->getSecret('chatgptKey');

	    if (!$model || !$apikey)
	    {
	    	$this->error = 'Model or api key for chatgpt is missing, please add it in the system settings.';
	    	return false;
	    }

	    $url = 'https://api.openai.com/v1/chat/completions';
	    $authHeader = "Authorization: Bearer $apikey";

	    $content = $prompt . "\n<article>" . $article . "<article>";
	    if($example)
	    {
	    	$content .= "\n<example>" . $example . "</example>";
	    }

	    $postdata = [
	        'model' => $model,
	        'messages' => [
	            [
	                'role' => 'system',
	                'content' => $this->getSystemMessage(), 
	            ],
	            [
	                'role' => 'user',
	                'content' => $content
	            ],
	        ],
	        'temperature' => 0.7,
	        'max_tokens' => 8000,
	    ];

	    $apiservice = new ApiCalls();
	    $apiservice->setTimeout(120);
	    $apiResponse = $apiservice->makePostCall($url, $postdata, $authHeader);

	    if (!$apiResponse)
	    {
	    	$this->error = 'Failed to communicate with ChatGPT: ' . $apiservice->getError();
	    	return false;
	    }

	    $data = json_decode($apiResponse, true);

	    if(isset($data['error']))
	    {
	    	$this->error = 'ChatGPT returned and error';
	    	if(isset($data['error']['message']))
	    	{
	    		$this->error = $data['error']['message'];
	    	}

	    	return false;
	    }

	    if (!isset($data['choices'][0]['message']['content']) || !is_string($data['choices'][0]['message']['content']))
	    {
	        $this->error = 'ChatGPT did not return a valid answer.';
	        return false;
	    }

	    $answer = trim($data['choices'][0]['message']['content']);

	    return $answer;
	}

	public function promptClaude($promptname, $prompt, $article, $example)
	{
	    # Check if user has accepted 
	    $settingsModel = new Settings();
	    $model = $this->settings['claudeModel'] ?? false;
	    $apikey = $settingsModel->getSecret('claudeKey');

	    if (!$model || !$apikey)
	    {
	        $this->error = 'Model or API key for Claude is missing, please add it in the system settings.';
	        return false;
	    }

	    $url = 'https://api.anthropic.com/v1/messages';
	    $headers = [
	        "x-api-key: $apikey",
	        "anthropic-version: 2023-06-01"
	    ];

	    $content = $prompt . "\n<article>" . $article . "<article>";
	    if($example)
	    {
	    	$content .= "\n<example>" . $example . "</example>";
	    }

	    $postdata = [
	        'model' => $model,
	        'system' => $this->getSystemMessage(),
	        'messages' => [
	            [
	                'role' => 'user',
	                'content' => $content
	            ],
	        ],
	        'temperature' => 0.7,
	        'max_tokens' => 8000,
	    ];

	    $apiservice = new ApiCalls();
	    $apiservice->setTimeout(120);
	    $apiResponse = $apiservice->makePostCall($url, $postdata, $headers);

	    if (!$apiResponse) {
	        $this->error = 'Failed to communicate with Claude: ' . $apiservice->getError();
	        return false;
	    }

	    $data = json_decode($apiResponse, true);

	    if (isset($data['error']))
	    {
	        $this->error = 'Claude API returned an error';
	        if (isset($data['error']['message']))
	        {
	            $this->error = $data['error']['message'];
	        }
	        return false;
	    }

	    if (!isset($data['content'][0]['text']) || !is_string($data['content'][0]['text']))
	    {
	        $this->error = 'Claude did not return a valid answer.';
	        return false;
	    }

	    return trim($data['content'][0]['text']);
	}

	# NOT READY YET
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
}