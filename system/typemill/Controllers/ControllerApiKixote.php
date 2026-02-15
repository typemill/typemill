<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\License;
use Typemill\Models\Settings;
use Typemill\Models\User;
use Typemill\Models\ApiCalls;
use Typemill\Models\Multilang;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
use Typemill\Models\Meta;
use Typemill\Static\Translations;
use Symfony\Component\Yaml\Yaml;

class ControllerApiKixote extends Controller
{
	private $error = false;

    private array $aimodels = [

        // OpenAI
        'gpt-5.2' => [
            'input' => 64000,
            'max'   => 200000,
        ],

        'gpt-5' => [
            'input' => 32000,
            'max'   => 128000,
        ],

        'gpt-4.1' => [
            'input' => 16000,
            'max'   => 64000,
        ],

        // Anthropic
        'opus' => [
            'input' => 64000,
            'max'   => 200000,
        ],

        'sonnet' => [
            'input' => 32000,
            'max'   => 128000,
        ],

        'haiku' => [
            'input' => 8000,
            'max'   => 32000,
        ],

        // Small models
        'nano' => [
            'input' => 4000,
            'max'   => 16000,
        ],

        'default' => [
	        'input' => 16000,
	        'max'   => 64000,
        ],
    ];

    private $aiservice = null;

    private $aimodel = null;

    private $apikey = null;

	private function setAiInfo()
	{
	    $this->aiservice = $this->settings['aiservice'] ?? null;

	    $this->aimodel = match ($this->aiservice) {
	        'chatgpt' => $this->settings['chatgptModel'] ?? null,
	        'claude'  => $this->settings['claudeModel'] ?? null,
	        default   => null,
	    };

	    $settingsModel = new Settings();
	    $this->apikey = match ($this->aiservice) {
	        'chatgpt' => $settingsModel->getSecret('chatgptKey'),
	        'claude'  => $settingsModel->getSecret('claudeKey'),
	        default   => null,
	    };

		$missing = [];
		if (!$this->aiservice) { $missing[] = 'AI service'; }
		if (!$this->aimodel) { $missing[] = 'AI model'; }
		if (!$this->apikey) { $missing[] = 'API key'; }
		if (!empty($missing))
		{
			$this->error = 'Missing configuration: ' . implode(', ', $missing);

			return false;
		}

		return true;
	}

    /**
     * Get token limits for a model name
     */
    private function getTokenInfo(): array
    {
        $aimodel = strtolower($this->aimodel);

        foreach ($this->aimodels as $key => $limits)
        {
            if (str_contains($aimodel, $key))
            {
                return $limits;
            }
        }

        return $this->aimodels['default'];
    }

    private function getInputBudget(): int
    {
        return $this->getTokenInfo($this->aimodels)['input'];
    }

    private function getMaxBudget(): int
    {
        return $this->getTokenInfo($this->aimodels)['max'];
    }

	private function getOutputBudget($content)
	{
	    $inputTokens 	= (int) (mb_strlen($content, 'UTF-8') / 4);
	    $total 			= $this->getMaxBudget();
	    $safety 		= 2000;

	    $available 		= $total - $inputTokens - $safety;

	    if ($available < 250)
	    {
	        return 250;
	    }

		$hardCap = (int) ($this->settings['aioutputtoken'] ?? 4000);

		# Clamp: min 256, max 12000
		$hardCap = max(256, min(12000, $hardCap));

	    return min($available, $hardCap);
	}

	private function getTemperature()
	{
		$temperature = (float) ($this->settings['aitemperature'] ?? 0.7);

		# Clamp: 0.0 – 1.0
		$temperature = max(0.0, min(1.0, $temperature));

		return $temperature;
	}

	private $system = 	'You are a content editor and writing assistant.'
		          		. ' If the user prompt does not explicitly specify otherwise,'
		          		. ' apply the prompt to the provided article inside the <article></article> tag and return only the updated article in Markdown syntax,'
		          		. ' without any extra comments or explanations.'
		          		. ' If you find the tag <focus></focus>,'
		          		. ' modify only the content inside these tags and leave everything else unchanged.' 
		          		. ' Always return the full article with clean markdown format and without the tags <article> and <focus>.';

	private function setSystemMessage($message)
	{
		$this->system = $message;
	}

	private function getSystemMessage()
	{
		return $this->system;		     
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

		$aisettings = $this->setAiInfo();
		if(!$aisettings)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $this->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

		$userdata 		= $user->getUserData();
		if(isset($userdata['aiservices']) && in_array($this->aiservice, $userdata['aiservices']))
		{
			$useragreement = true;
			switch ($this->aiservice)
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

	    $response->getBody()->write(json_encode([
	        'message' 		=> 'Success',
	        'aiservice' 	=> $this->aiservice,
	        'useragreement' => $useragreement,
	        'tokenstats' 	=> $tokenstats
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function agreeToAiService(Request $request, Response $response): Response
	{
		$aisettings = $this->setAiInfo();
		if(!$aisettings)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $this->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

		$user 			= new User();
		$username 		= $request->getAttribute('c_username');

		if(!$user->setUserWithPassword($username))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We did not find the a user or usermail.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$agreements = $user->getValue('aiservices');

		if(!$agreements)
		{
			$agreements = [$this->aiservice];
		}
		elseif(!isset($agreements[$this->aiservice]))
		{
			$agreements[] = $this->aiservice;
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

		$aisettings = $this->setAiInfo();
		if(!$aisettings)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $this->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

	    $promptname 	= $params['name'] ?? '';
	    $prompt 		= $params['prompt'] ?? '';
	    $article 		= $params['article'] ?? '';
	    $example 		= $params['example'] ?? false;
		 
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
				$allContent 	= $prompt . $article . $example;
				$length 		= strlen($allContent);
				$maxInputTokens = $this->getInputBudget();
				$maxlength 		= $maxInputTokens * 4;
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

	    switch ($this->aiservice) {
	    	case 'chatgpt':
	    		$answer = $this->promptChatGPT($promptname, $prompt, $article, $example, 'example');
	    		break;

	    	case 'claude':
	    		$answer = $this->promptClaude($promptname, $prompt, $article, $example, 'example');
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

	public function promptChatGPT($promptname, $prompt, $article, $addition = null, $tag = null)
	{
	    $url = 'https://api.openai.com/v1/chat/completions';
	    $authHeader = "Authorization: Bearer $this->apikey";

	    $content = $prompt . "\n<article>" . $article . "<article>";
	    if($addition && $tag)
	    {
	    	$content .= "\n<" . $tag . ">" . $addition . "</" . $tag . ">";
	    }

	    $postdata = [
	        'model' => $this->aimodel,
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
	        'temperature' => $this->getTemperature(),
	        'max_tokens' => $this->getOutputBudget($content)
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

	public function promptClaude($promptname, $prompt, $article, $addition = null, $tag = null)
	{
	    $url = 'https://api.anthropic.com/v1/messages';
	    $headers = [
	        "x-api-key: $this->apikey",
	        "anthropic-version: 2023-06-01"
	    ];

	    $content = $prompt . "\n<article>" . $article . "<article>";
	    if($addition && $tag)
	    {
	    	$content .= "\n<" . $tag . ">" . $addition . "</" . $tag . ">";
	    }

	    $postdata = [
	        'model' => $this->aimodel,
	        'system' => $this->getSystemMessage(),
	        'messages' => [
	            [
	                'role' => 'user',
	                'content' => $content
	            ],
	        ],
	        'temperature' => $this->getTemperature(),
	        'max_tokens' => $this->getOutputBudget($content)
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

	/****************************
	 * Translation Features 
	 * *************************/

	public function autotrans(Request $request, Response $response)
	{
	    $params 	= $request->getParsedBody();
		$lang 		= $params['lang'] ?? false;
		$pageid 	= $params['pageid'] ?? false;
		$urlinfo 	= $this->c->get('urlinfo');

	    if (!$pageid || !$lang)
	    {
	        $response->getBody()->write(json_encode([
	            'message' => 'Prompt is missing or invalid.'
	        ]));
	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
	    }

		$aisettings = $this->setAiInfo();
		if(!$aisettings)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $this->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

        $multilang 			= new Multilang();
		$multilangIndex 	= $multilang->getMultilangIndex();
        if(!$multilangIndex)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
        if(!$multilangData or !isset($multilangData[$lang]))
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the page id in the mulitlangindex'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$navigation 		= new Navigation();
		$url 				= $multilangData[$lang];

		# configure multilang and multiproject
		$navigation->setProject($this->settings, $url, $dispatcher = false);

		$item 				= $navigation->getItemForUrl($url, $urlinfo, $lang);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => 'page not found',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# GET THE CONTENT
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $dispather = false);
		$draftMarkdown		= $content->getDraftMarkdown($item);
		$markdown 			= $content->markdownArrayToText($draftMarkdown);

	    $promptname 	= 'translateArticle';
		$prompt 		= 'Translate the following article into ' 
						. $this->settings['projectinstances'][$lang] . ' (' . $lang . '). '
						. 'Preserve the original Markdown structure exactly (headings, lists, links, emphasis, code blocks). '
						. 'Do not translate or alter Markdown syntax itself. '
						. 'Rewrite sentences freely where necessary so the translation sounds natural, fluent, and idiomatic in ' . $this->settings['projectinstances'][$lang] . '. '
				        . 'Return ONLY the translation content in pure Markdown. '
				        . 'Do NOT include the <article> tag. '
				        . 'Do NOT add explanations, comments, headings, or any extra text.';
	    $article 		= $markdown;

	    switch ($this->aiservice) {
	    	case 'chatgpt':
	    		$answer = $this->promptChatGPT($promptname, $prompt, $article);
	    		break;
	    	
	    	case 'claude':
	    		$answer = $this->promptClaude($promptname, $prompt, $article);
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

	    $markdownArray 		= $content->markdownTextToArray($answer);

		$content->saveDraftMarkdown($item, $markdownArray);

		# GET THE META
		$meta 				= new Meta();
		$metadata  			= $meta->getMetaData($item);
		$metadata 			= $meta->addMetaDefaults($metadata, $item, $this->settings['author']);
		$metadata 			= $meta->addMetaTitleDescription($metadata, $item, $markdownArray);

		$yaml = Yaml::dump(
		    $metadata,
		    10, // depth
		    2,  // indentation
		    Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
		);

	    $promptname 		= 'translateMeta';
	    $prompt 			= 'Translate the following yaml configurations into ' . $this->settings['projectinstances'][$lang] . ' (' . $lang . '). Only tranlsate values on the right, not keys on the left.';
	    $article 			= $yaml;
		
		$system 			=  'You are a content editor and writing assistant.'
                 			. ' If the user prompt does not explicitly specify otherwise,'
                 			. ' apply the prompt to the provided content inside the <article></article> tag and return only the updated content in valid YAML format,'
                 			. ' without any extra comments, explanations, or formatting outside YAML.'
                 			. ' Preserve correct YAML syntax, indentation, quoting, and data types.'
                 			. ' Always return the full YAML document without the  <article> tag.'
							. ' For the field "navtitle", use a very short, natural navigation title.'
							. ' Prefer concise nouns or verb phrases and avoid unnecessary words.'
							. ' Example: Instead of "create your first page", use "create page".';

        $this->setSystemMessage($system);

	    switch ($this->aiservice) {
	    	case 'chatgpt':
	    		$answer = $this->promptChatGPT($promptname, $prompt, $article);
	    		break;
	    	
	    	case 'claude':
	    		$answer = $this->promptClaude($promptname, $prompt, $article);
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

		# validate meta
		$parsedYaml = Yaml::parse($answer);

		if($parsedYaml)
		{
	    	$meta->updateMeta($parsedYaml, $item);

			$naviFileName 		= $navigation->getNaviFileNameForPath($item->path);
	    	$navigation->clearNavigation();
		}

	    $response->getBody()->write(json_encode([
	        'message' 	=> 'Success',
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}	

	public function autotransUpdate(Request $request, Response $response)
	{
	    $params 	= $request->getParsedBody();
		$lang 		= $params['lang'] ?? false;
		$pageid 	= $params['pageid'] ?? false;
		$urlinfo 	= $this->c->get('urlinfo');

	    if (!$pageid || !$lang)
	    {
	        $response->getBody()->write(json_encode([
	            'message' => 'Page id or language is missing.'
	        ]));
	        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
	    }

		$aisettings = $this->setAiInfo();
		if(!$aisettings)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> $this->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

        $multilang 			= new Multilang();
		$multilangIndex 	= $multilang->getMultilangIndex();
        if(!$multilangIndex)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
        if(!$multilangData or !isset($multilangData[$lang]))
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the page id in the mulitlangindex'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$navigation 		= new Navigation();

	    $projects 			= $navigation->getAllProjects($this->settings);
		$baselang = null;
		foreach ($projects as $project)
		{
		    if (!empty($project['base']))
		    {
		        $baselang = $project['id'];
		        break;
		    }
		}

		$origUrl 			= $multilangData[$baselang] ?? false;		
		$transUrl 			= $multilangData[$lang] ?? false;

		if(!$origUrl OR !$transUrl)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find valid urls for the translation or article.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$origItem 			= $navigation->getItemForUrl($origUrl, $urlinfo, $baselang);
		if(!$origItem)
		{
			$response->getBody()->write(json_encode([
				'message' => 'Page for base language not found',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# configure multilang and multiproject
		$navigation->setProject($this->settings, $transUrl, $dispatcher = false);

		$transItem 			= $navigation->getItemForUrl($transUrl, $urlinfo, $lang);
		if(!$transItem)
		{
			$response->getBody()->write(json_encode([
				'message' => 'Translation page not found',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# GET THE CONTENT
		$content 				= new Content($urlinfo['baseurl'], $this->settings, $dispather = false);
		$transDraftMarkdown 	= $content->getDraftMarkdown($transItem);
		$transMarkdown 			= $content->markdownArrayToText($transDraftMarkdown);
		$origDraftMarkdown		= $content->getDraftMarkdown($origItem);
		$origMarkdown 			= $content->markdownArrayToText($origDraftMarkdown);

	    $promptname 	= 'translateArticleUpdate';
		$prompt 		= 'You will receive an <article> tag (source text) and a <translation> tag (existing translation). '
		        		. 'Read the article and update the translation into the language '
		        		. $this->settings['projectinstances'][$lang] . ' (' . $lang . '). '
				        . 'Update only those parts of the translation that differ in meaning, content, or structure from the article. '
				        . 'Do not rewrite unchanged parts. '
				        . 'Preserve the Markdown structure (headings, lists, links, emphasis, code blocks). '
				        . 'Do not translate or alter Markdown syntax. '
				        . 'Rewrite updated parts freely so the translation sounds natural, fluent, and idiomatic in '
				        . $this->settings['projectinstances'][$lang] . '. '
				        . 'Return ONLY the updated translation content in pure Markdown. '
				        . 'Do NOT include the <translation> tag. '
				        . 'Do NOT add explanations, comments, headings, or any extra text.';
	    $article 		= $origMarkdown;
	    $translation 	= $transMarkdown;

	    switch ($this->aiservice) {
	    	case 'chatgpt':
	    		$answer = $this->promptChatGPT($promptname, $prompt, $article, $translation, 'translation');
	    		break;
	    	
	    	case 'claude':
	    		$answer = $this->promptClaude($promptname, $prompt, $article, $translation, 'translation');
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

	    $markdownArray 		= $content->markdownTextToArray($answer);

		$content->saveDraftMarkdown($transItem, $markdownArray);
		
		# GET THE META
		$meta 				= new Meta();
		$origMetadata  		= $meta->getMetaData($origItem);
		$origMetadata 		= $meta->addMetaDefaults($origMetadata, $origItem, $this->settings['author']);
		$origMetadata 		= $meta->addMetaTitleDescription($origMetadata, $origItem, $origDraftMarkdown);
		$transMetadata  	= $meta->getMetaData($transItem);
		$transMetadata 		= $meta->addMetaDefaults($transMetadata, $transItem, $this->settings['author']);
		$transMetadata 		= $meta->addMetaTitleDescription($transMetadata, $transItem, $transDraftMarkdown);

		$origYaml = Yaml::dump(
		    $origMetadata,
		    10, // depth
		    2,  // indentation
		    Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
		);

		$transYaml = Yaml::dump(
		    $transMetadata,
		    10, // depth
		    2,  // indentation
		    Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
		);

		$system 			= 'You are a content editor and writing assistant. '
							. 'Always apply the user prompt to the content inside the <translation></translation> tag. '
							. 'Return only the updated content as a complete, valid YAML document. '
							. 'Do not include the <translation> tag. '
							. 'Do not add comments, explanations, headings, or extra text. '
							. 'Do not use Markdown or code blocks. '
							. 'Preserve correct YAML syntax, indentation, quoting, and data types. '
							. 'For the field "navtitle", use a very short, natural navigation title. '
							. 'Prefer concise nouns or verb phrases and avoid unnecessary words. '
							. 'Example: Instead of "create your first page", use "create page".';
        $this->setSystemMessage($system);
	    $promptname 		= 'translateMetaUpdate';
		$prompt 			= 'You will receive an <article> tag (source text) and a <translation> tag (existing translation), both in YAML syntax. '
							. 'Read the original YAML definitions in the <article> tag and update the translated YAML definitions in the <translation> tag into the language '
							. $this->settings['projectinstances'][$lang] . ' (' . $lang . '). '
							. 'Update only those parts of the translation that differ from the YAML definitions in the <article> tag. '
							. 'Do not rewrite unchanged parts. '
							. 'NEVER change values for "pageid" and "translation_for". '
							. 'Translate only YAML values. Never translate keys. '
							. 'Return ONLY the updated translation content as valid YAML. '
							. 'Do NOT include the <translation> tag. '
							. 'Do NOT add explanations, comments, headings, Markdown, or extra text.';
	    $article 			= $origYaml;
	    $translation 		= $transYaml;

	    switch ($this->aiservice) {
	    	case 'chatgpt':
	    		$answer = $this->promptChatGPT($promptname, $prompt, $article, $translation, 'translation');
	    		break;
	    	
	    	case 'claude':
	    		$answer = $this->promptClaude($promptname, $prompt, $article, $translation, 'translation');
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

		# validate meta
		$parsedYaml = Yaml::parse($answer);

		if($parsedYaml)
		{
	    	$meta->updateMeta($parsedYaml, $transItem);
		}

	    $navigation->clearNavigation();

	    $response->getBody()->write(json_encode([
	        'message' 	=> 'Success',
	    ]));

	    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}