<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Validation;
use Typemill\Models\Settings;
use Typemill\Models\User;
use Typemill\Models\AiAdapter;
use Typemill\Models\Multilang;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
use Typemill\Models\Meta;
use Typemill\Models\KixoteHelp;
use Typemill\Static\Translations;
use Symfony\Component\Yaml\Yaml;

class ControllerApiKixote extends Controller
{
	private $error = false;

	private ?string $aiadapter      = null;
	private ?string $aibaseurl      = null;
	private ?string $aimodel        = null;
	private ?string $apikey         = null;
	private string  $aiprovider     = '';
	private string  $aiproviderterms = '';

	private string $system =
		'You are a content editor and writing assistant.'
		. ' If the user prompt does not explicitly specify otherwise,'
		. ' apply the prompt to the provided article inside the <article></article> tag and return only the updated article in Markdown syntax,'
		. ' without any extra comments or explanations.'
		. ' If you find the tag <focus></focus>,'
		. ' modify only the content inside these tags and leave everything else unchanged.'
		. ' Always return the full article with clean markdown format and without the tags <article> and <focus>.';

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private function setAiInfo(): bool
	{
		// Read new generic settings first
		$adapter = $this->settings['ai_adapter'] ?? null;
		$baseUrl = $this->settings['ai_base_url'] ?? null;
		$model   = $this->settings['ai_model'] ?? null;

		$settingsModel = new Settings();
		$apikey = $settingsModel->getSecret('ai_api_key');

		// Fallback: migrate from old provider-specific settings
		if (!$adapter || $adapter === 'none') {
			$oldService = $this->settings['aiservice'] ?? null;
			if ($oldService === 'chatgpt') {
				$adapter = 'openai';
				$baseUrl = $baseUrl ?: 'https://api.openai.com/v1';
				$model   = $model ?: ($this->settings['chatgptModel'] ?? null);
				$apikey  = $apikey ?: $settingsModel->getSecret('chatgptKey');
			} elseif ($oldService === 'claude') {
				$adapter = 'anthropic';
				$baseUrl = $baseUrl ?: 'https://api.anthropic.com/v1';
				$model   = $model ?: ($this->settings['claudeModel'] ?? null);
				$apikey  = $apikey ?: $settingsModel->getSecret('claudeKey');
			}
		}

		$this->aiadapter       = $adapter;
		$this->aibaseurl       = rtrim($baseUrl ?? '', '/');  // normalize trailing slash
		$this->aimodel         = $model;
		$this->apikey          = $apikey;
		$this->aiprovider      = $this->settings['ai_provider_name'] ?? '';
		$this->aiproviderterms = $this->settings['ai_provider_terms'] ?? '';

		// adapter, base URL and model are required; api key is optional
		$missing = [];
		if (!$this->aiadapter || $this->aiadapter === 'none') { $missing[] = 'AI adapter'; }
		if (!$this->aibaseurl)                                 { $missing[] = 'API base URL'; }
		if (!$this->aimodel)                                   { $missing[] = 'AI model'; }

		if (!empty($missing)) {
			$this->error = 'Missing configuration: ' . implode(', ', $missing);
			return false;
		}

		return true;
	}

	/**
	 * Returns the agreement identifier shown to / stored for users.
	 * Uses the human-readable provider name when configured, otherwise
	 * falls back to the adapter type so the key is always non-empty.
	 */
	private function getAgreementIdentifier(): string
	{
		return !empty($this->aiprovider) ? $this->aiprovider : $this->aiadapter;
	}

	private function setSystemMessage(string $message): void
	{
		$this->system = $message;
	}

	private function getSystemMessage(): string
	{
		return $this->system;
	}

	private function getTemperature(): float
	{
		$temperature = (float) ($this->settings['aitemperature'] ?? 0.7);
		// Clamp: 0.0 – 1.0
		return max(0.0, min(1.0, $temperature));
	}

	private function getOutputBudget(string $content): int
	{
		$hardCap = (int) ($this->settings['aioutputtoken'] ?? 4000);
		// Clamp: min 256, max 12000
		return max(256, min(12000, $hardCap));
	}

	/**
	 * Dispatch a fully-assembled user message to the configured AI provider.
	 * Callers are responsible for building $userMessage (prompt + tagged XML content).
	 * Pass $systemMessageOverride for calls that need a different system prompt (e.g. YAML translation).
	 */
	private function promptGeneric(
		string  $userMessage,
		?string $systemMessageOverride = null
	): string|false
	{
		$systemMessage = $systemMessageOverride ?? $this->getSystemMessage();
		$maxTokens     = $this->getOutputBudget($userMessage);
		$temperature   = $this->getTemperature();

		$adapter = AiAdapter::create(
			$this->aiadapter,
			$this->aibaseurl,
			$this->aimodel,
			$this->apikey ?? ''
		);

		$answer = $adapter->chat($systemMessage, $userMessage, $maxTokens, $temperature);

		if ($answer === false) {
			$this->error = $adapter->getError();
		}

		return $answer;
	}

	// -------------------------------------------------------------------------
	// Kixote prompt settings (custom prompt list)
	// -------------------------------------------------------------------------

	public function getKixoteSettings(Request $request, Response $response)
	{
		$settingsModel  = new Settings();
		$kixoteSettings = $settingsModel->getKixoteSettings();

		if (!$kixoteSettings) {
			$response->getBody()->write(json_encode([
				'message' => 'could not load kixote settings.'
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'kixotesettings' => $kixoteSettings
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function updateKixoteSettings(Request $request, Response $response)
	{
		$params         = $request->getParsedBody();
		$kixoteSettings = $params['kixotesettings'] ?? false;
		$validate       = new Validation();
		$cleanSettings  = [];

		if (isset($kixoteSettings['promptlist'])) {
			$promptErrors = false;
			foreach ($kixoteSettings['promptlist'] as $name => $values) {
				$validInput = $validate->kixotePrompt($values);
				if ($validInput !== true) {
					$promptErrors = true;
					$kixoteSettings['promptlist'][$name]['errors'] = $validInput;
				} else {
					$cleanSettings['promptlist'][$name] = $values;
					unset($kixoteSettings['promptlist'][$name]['errors']);
				}
			}

			if ($promptErrors) {
				$response->getBody()->write(json_encode([
					'message'        => 'please correct the errors in the form',
					'kixotesettings' => $kixoteSettings
				]));
				return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
			}
		}

		$settingsModel = new Settings();
		$result        = $settingsModel->updateKixoteSettings($cleanSettings);

		if (!$result) {
			$kixoteSettings = $settingsModel->getKixoteSettings();
			$response->getBody()->write(json_encode([
				'message'        => 'error while saving settings.',
				'kixotesettings' => $kixoteSettings
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'kixotesettings' => $kixoteSettings
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	// -------------------------------------------------------------------------
	// Token stats & user agreement
	// -------------------------------------------------------------------------

	public function getTokenStats(Request $request, Response $response): Response
	{
		$useragreement = false;
		$user          = new User();
		$username      = $request->getAttribute('c_username');

		if (!$user->setUser($username)) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the a user.')
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$identifier = $this->getAgreementIdentifier();
		$userdata   = $user->getUserData();
		$agreements = $userdata['aiservices'] ?? [];
		if (in_array($identifier, $agreements)) {
			$useragreement = true;
		}

		$aiinfo = [
			'adapter'       => $this->aiadapter,
			'model'         => $this->aimodel,
			'providername'  => $this->aiprovider,
			'providerterms' => $this->aiproviderterms,
		];

		$response->getBody()->write(json_encode([
			'message'       => 'Success',
			'aiservice'     => $this->aiadapter,
			'useragreement' => $useragreement,
			'aiinfo'        => $aiinfo,
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function agreeToAiService(Request $request, Response $response): Response
	{
		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$user     = new User();
		$username = $request->getAttribute('c_username');

		if (!$user->setUserWithPassword($username)) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the a user or usermail.')
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$identifier = $this->getAgreementIdentifier();
		$agreements = $user->getValue('aiservices');

		if (!$agreements) {
			$agreements = [$identifier];
		} elseif (!in_array($identifier, $agreements)) {
			$agreements[] = $identifier;
		}

		$user->setValue('aiservices', $agreements);
		if ($user->updateUser() !== true) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We could not update your user settings, please try again or agree to ' . $identifier . ' in your user profile.')
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'Success'
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	// -------------------------------------------------------------------------
	// Model list
	// -------------------------------------------------------------------------

	public function getModels(Request $request, Response $response): Response
	{
		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode(['message' => $this->error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$adapter = AiAdapter::create(
			$this->aiadapter,
			$this->aibaseurl,
			$this->aimodel,
			$this->apikey ?? ''
		);

		$models = $adapter->listModels();

		if ($models === false) {
			$response->getBody()->write(json_encode(['message' => $adapter->getError()]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'Success',
			'models'  => $models,
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	// -------------------------------------------------------------------------
	// Connection test
	// -------------------------------------------------------------------------

	public function testConnection(Request $request, Response $response): Response
	{
		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode(['message' => $this->error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$answer = $this->promptGeneric('Reply with the single word: ok');

		if ($answer === false) {
			$response->getBody()->write(json_encode([
				'message' => 'Connection failed: ' . $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'Connection successful',
			'model'   => $this->aimodel,
			'adapter' => $this->aiadapter,
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	// -------------------------------------------------------------------------
	// Prompt (main AI action)
	// -------------------------------------------------------------------------

	public function prompt(Request $request, Response $response)
	{
		$params = $request->getParsedBody();

		if (empty($params['prompt']) || !is_string($params['prompt'])) {
			$response->getBody()->write(json_encode([
				'message' => 'Prompt is missing or invalid.'
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if (empty($params['article']) || !is_string($params['article'])) {
			$response->getBody()->write(json_encode([
				'message' => 'Article is missing or invalid.'
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$prompt  = $params['prompt'] ?? '';
		$article = $params['article'] ?? '';
		$example = $params['example'] ?? false;

		if ($example && $example != "") {
			$validation = new Validation();
			$v = $validation->returnValidator(['content' => $example]);
			$v->rule('markdownSecure', 'content');
			if (!$v->validate()) {
				$example = false;
			} else {
				// Fixed conservative character limit: ~15k tokens, safe for most providers
				$maxInputChars = 60000;
				$allContent    = $prompt . $article . $example;
				if (mb_strlen($allContent, 'UTF-8') > $maxInputChars) {
					$overLimit = mb_strlen($allContent, 'UTF-8') - $maxInputChars;
					$keep      = mb_strlen($example, 'UTF-8') - $overLimit;
					$example   = $keep > 0 ? mb_substr($example, 0, $keep) : false;
				}
			}
		}

		$userMessage = $prompt . "\n<article>" . $article . "</article>";
		if ($example) {
			$userMessage .= "\n<example>" . $example . "</example>";
		}

		$answer = $this->promptGeneric($userMessage);

		if (!$answer) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'Success',
			'answer'  => $answer,
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	// -------------------------------------------------------------------------
	// Translation features
	// -------------------------------------------------------------------------

	public function autotrans(Request $request, Response $response)
	{
		$params  = $request->getParsedBody();
		$lang    = $params['lang'] ?? false;
		$pageid  = $params['pageid'] ?? false;
		$urlinfo = $this->c->get('urlinfo');

		if (!$pageid || !$lang) {
			$response->getBody()->write(json_encode([
				'message' => 'Prompt is missing or invalid.'
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$multilang      = new Multilang();
		$multilangIndex = $multilang->getMultilangIndex();
		if (!$multilangIndex) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$multilangData = $multilang->getMultilangData($pageid, $multilangIndex);
		if (!$multilangData || !isset($multilangData[$lang])) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the page id in the mulitlangindex'),
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$navigation = new Navigation();
		$url        = $multilangData[$lang];

		// configure multilang and multiproject
		$navigation->setProject($this->settings, $url, $dispatcher = false);

		$item = $navigation->getItemForUrl($url, $urlinfo, $lang);
		if (!$item) {
			$response->getBody()->write(json_encode([
				'message' => 'page not found',
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		// GET THE CONTENT
		$content      = new Content($urlinfo['baseurl'], $this->settings, $dispatcher = false);
		$draftMarkdown = $content->getDraftMarkdown($item);
		$markdown      = $content->markdownArrayToText($draftMarkdown);

		$prompt = 'Translate the following article into '
			. $this->settings['projectinstances'][$lang] . ' (' . $lang . '). '
			. 'Preserve the original Markdown structure exactly (headings, lists, links, emphasis, code blocks). '
			. 'Do not translate or alter Markdown syntax itself. '
			. 'Rewrite sentences freely where necessary so the translation sounds natural, fluent, and idiomatic in ' . $this->settings['projectinstances'][$lang] . '. '
			. 'Return ONLY the translation content in pure Markdown. '
			. 'Do NOT include the <article> tag. '
			. 'Do NOT add explanations, comments, headings, or any extra text.';

		$userMessage = $prompt . "\n<article>" . $markdown . "</article>";
		$answer      = $this->promptGeneric($userMessage);

		if (!$answer) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$markdownArray = $content->markdownTextToArray($answer);
		$content->saveDraftMarkdown($item, $markdownArray);

		// GET THE META
		$meta     = new Meta();
		$metadata = $meta->getMetaData($item);
		$metadata = $meta->addMetaDefaults($metadata, $item, $this->settings['author']);
		$metadata = $meta->addMetaTitleDescription($metadata, $item, $markdownArray);

		$yaml = Yaml::dump(
			$metadata,
			10, // depth
			2,  // indentation
			Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
		);

		$metaPrompt = 'Translate the following yaml configurations into ' . $this->settings['projectinstances'][$lang] . ' (' . $lang . '). Only translate values on the right, not keys on the left.';

		$metaSystemMessage =
			'You are a content editor and writing assistant.'
			. ' If the user prompt does not explicitly specify otherwise,'
			. ' apply the prompt to the provided content inside the <article></article> tag and return only the updated content in valid YAML format,'
			. ' without any extra comments, explanations, or formatting outside YAML.'
			. ' Preserve correct YAML syntax, indentation, quoting, and data types.'
			. ' Always return the full YAML document without the <article> tag.'
			. ' For the field "navtitle", use a very short, natural navigation title.'
			. ' Prefer concise nouns or verb phrases and avoid unnecessary words.'
			. ' Example: Instead of "create your first page", use "create page".';

		$metaUserMessage = $metaPrompt . "\n<article>" . $yaml . "</article>";
		$metaAnswer      = $this->promptGeneric($metaUserMessage, $metaSystemMessage);

		if (!$metaAnswer) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		// validate and save meta
		$parsedYaml = Yaml::parse($metaAnswer);
		if ($parsedYaml) {
			$meta->updateMeta($parsedYaml, $item);
			$navigation->getNaviFileNameForPath($item->path);
			$navigation->clearNavigation();
		}

		$response->getBody()->write(json_encode([
			'message' => 'Success',
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function autotransUpdate(Request $request, Response $response)
	{
		$params  = $request->getParsedBody();
		$lang    = $params['lang'] ?? false;
		$pageid  = $params['pageid'] ?? false;
		$urlinfo = $this->c->get('urlinfo');

		if (!$pageid || !$lang) {
			$response->getBody()->write(json_encode([
				'message' => 'Page id or language is missing.'
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$multilang      = new Multilang();
		$multilangIndex = $multilang->getMultilangIndex();
		if (!$multilangIndex) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$multilangData = $multilang->getMultilangData($pageid, $multilangIndex);
		if (!$multilangData || !isset($multilangData[$lang])) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the page id in the mulitlangindex'),
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$navigation = new Navigation();
		$projects   = $navigation->getAllProjects($this->settings);
		$baselang   = null;
		foreach ($projects as $project) {
			if (!empty($project['base'])) {
				$baselang = $project['id'];
				break;
			}
		}

		$origUrl  = $multilangData[$baselang] ?? false;
		$transUrl = $multilangData[$lang] ?? false;

		if (!$origUrl || !$transUrl) {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find valid urls for the translation or article.'),
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$origItem = $navigation->getItemForUrl($origUrl, $urlinfo, $baselang);
		if (!$origItem) {
			$response->getBody()->write(json_encode([
				'message' => 'Page for base language not found',
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		// configure multilang and multiproject
		$navigation->setProject($this->settings, $transUrl, $dispatcher = false);

		$transItem = $navigation->getItemForUrl($transUrl, $urlinfo, $lang);
		if (!$transItem) {
			$response->getBody()->write(json_encode([
				'message' => 'Translation page not found',
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		// GET THE CONTENT
		$content           = new Content($urlinfo['baseurl'], $this->settings, $dispatcher = false);
		$transDraftMarkdown = $content->getDraftMarkdown($transItem);
		$transMarkdown      = $content->markdownArrayToText($transDraftMarkdown);
		$origDraftMarkdown  = $content->getDraftMarkdown($origItem);
		$origMarkdown       = $content->markdownArrayToText($origDraftMarkdown);

		$prompt = 'You will receive an <article> tag (source text) and a <translation> tag (existing translation). '
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

		$userMessage = $prompt
			. "\n<article>" . $origMarkdown . "</article>"
			. "\n<translation>" . $transMarkdown . "</translation>";

		$answer = $this->promptGeneric($userMessage);

		if (!$answer) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$markdownArray = $content->markdownTextToArray($answer);
		$content->saveDraftMarkdown($transItem, $markdownArray);

		// GET THE META
		$meta         = new Meta();
		$origMetadata = $meta->getMetaData($origItem);
		$origMetadata = $meta->addMetaDefaults($origMetadata, $origItem, $this->settings['author']);
		$origMetadata = $meta->addMetaTitleDescription($origMetadata, $origItem, $origDraftMarkdown);
		$transMetadata = $meta->getMetaData($transItem);
		$transMetadata = $meta->addMetaDefaults($transMetadata, $transItem, $this->settings['author']);
		$transMetadata = $meta->addMetaTitleDescription($transMetadata, $transItem, $transDraftMarkdown);

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

		$metaSystemMessage =
			'You are a content editor and writing assistant. '
			. 'Always apply the user prompt to the content inside the <translation></translation> tag. '
			. 'Return only the updated content as a complete, valid YAML document. '
			. 'Do not include the <translation> tag. '
			. 'Do not add comments, explanations, headings, or extra text. '
			. 'Do not use Markdown or code blocks. '
			. 'Preserve correct YAML syntax, indentation, quoting, and data types. '
			. 'For the field "navtitle", use a very short, natural navigation title. '
			. 'Prefer concise nouns or verb phrases and avoid unnecessary words. '
			. 'Example: Instead of "create your first page", use "create page".';

		$metaPrompt = 'You will receive an <article> tag (source text) and a <translation> tag (existing translation), both in YAML syntax. '
			. 'Read the original YAML definitions in the <article> tag and update the translated YAML definitions in the <translation> tag into the language '
			. $this->settings['projectinstances'][$lang] . ' (' . $lang . '). '
			. 'Update only those parts of the translation that differ from the YAML definitions in the <article> tag. '
			. 'Do not rewrite unchanged parts. '
			. 'NEVER change values for "pageid" and "translation_for". '
			. 'Translate only YAML values. Never translate keys. '
			. 'Return ONLY the updated translation content as valid YAML. '
			. 'Do NOT include the <translation> tag. '
			. 'Do NOT add explanations, comments, headings, Markdown, or extra text.';

		$metaUserMessage = $metaPrompt
			. "\n<article>" . $origYaml . "</article>"
			. "\n<translation>" . $transYaml . "</translation>";

		$metaAnswer = $this->promptGeneric($metaUserMessage, $metaSystemMessage);

		if (!$metaAnswer) {
			$response->getBody()->write(json_encode([
				'message' => $this->error
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		// validate and save meta
		$parsedYaml = Yaml::parse($metaAnswer);
		if ($parsedYaml) {
			$meta->updateMeta($parsedYaml, $transItem);
		}

		$navigation->clearNavigation();

		$response->getBody()->write(json_encode([
			'message' => 'Success',
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	// -------------------------------------------------------------------------
	// Help — ask docs.typemill.net using local AI adapter
	// -------------------------------------------------------------------------

	public function help(Request $request, Response $response): Response
	{
		$body = $request->getParsedBody();
		if (!is_array($body)) {
			$body = json_decode((string) $request->getBody(), true) ?? [];
		}

		$question = trim($body['question'] ?? '');
		$history  = $body['history'] ?? [];

		// ── Validation ──
		if ($question === '') {
			$response->getBody()->write(json_encode(['error' => 'Question is required.']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		if (mb_strlen($question) > 500) {
			$response->getBody()->write(json_encode(['error' => 'Question too long (max 500 characters).']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		if (substr_count($question, "\n") >= 5) {
			$response->getBody()->write(json_encode(['error' => 'Question contains too many lines.']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if (!is_array($history)) {
			$history = [];
		}
		$history = array_slice($history, -6);
		$cleanHistory = [];
		foreach ($history as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$role    = strtolower(trim($entry['role'] ?? ''));
			$content = strip_tags($entry['content'] ?? '');
			if ($content === '' || ($role !== 'user' && $role !== 'assistant')) {
				continue;
			}
			$cleanHistory[] = ['role' => $role, 'content' => $content];
		}

		// ── AI must be configured ──
		$aisettings = $this->setAiInfo();
		if (!$aisettings) {
			$response->getBody()->write(json_encode(['error' => $this->error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		// ── Public key hash for remote auth ──
		$pkeyfile = getcwd() . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'public_key.pem';
		$publicKeyHash = '';
		if (file_exists($pkeyfile) && is_readable($pkeyfile)) {
			$content = file_get_contents($pkeyfile);
			if ($content !== false) {
				$publicKeyHash = md5($content);
			}
		}

		// ── Run agent loop ──
		$kixoteHelp = new KixoteHelp($this->settings, $publicKeyHash);

		$callback = function (string $conversation, string $systemPrompt) {
			$answer = $this->promptGeneric($conversation, $systemPrompt);
			return $answer === false ? '' : $answer;
		};

		$result = $kixoteHelp->runAgentLoop($question, $cleanHistory, $callback);

		if (!empty($result['error'])) {
			$response->getBody()->write(json_encode(['error' => $result['error']]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
		}

		$response->getBody()->write(json_encode([
			'answer'  => $result['answer'] ?? '',
			'sources' => $result['sources'] ?? [],
		]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}
