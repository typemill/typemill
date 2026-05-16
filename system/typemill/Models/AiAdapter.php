<?php

namespace Typemill\Models;

/**
 * Contract for all AI provider adapters.
 */
interface AiAdapterInterface
{
    /**
     * Send a chat request and return the plain-text answer.
     *
     * @param string $systemMessage  The system/role instruction.
     * @param string $userMessage    The assembled user message (prompt + tagged content).
     * @param int    $maxTokens      Maximum output tokens.
     * @param float  $temperature    Temperature (0.0–1.0).
     * @return string|false          The answer string, or false on failure.
     */
    public function chat(
        string $systemMessage,
        string $userMessage,
        int    $maxTokens,
        float  $temperature
    ): string|false;

    /**
     * Fetch the list of available models from the provider.
     * Both OpenAI-compatible and Anthropic expose GET {baseUrl}/models.
     *
     * @return array|false  Array of ['id' => string] entries, or false on failure.
     */
    public function listModels(): array|false;

    public function getError(): string;
}

/**
 * Adapter for OpenAI-compatible APIs.
 * Supports: OpenAI, Ollama, LM Studio, LocalAI, vLLM, OpenRouter, and others.
 * Endpoint: POST {baseUrl}/chat/completions
 */
class OpenAiAdapter implements AiAdapterInterface
{
    private string $baseUrl;
    private string $model;
    private string $apikey;
    private string $error = '';

    public function __construct(string $baseUrl, string $model, string $apikey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model   = $model;
        $this->apikey  = $apikey;
    }

    public function chat(string $systemMessage, string $userMessage, int $maxTokens, float $temperature): string|false
    {
        $url = $this->baseUrl . '/chat/completions';

        $headers = [];
        if (!empty($this->apikey)) {
            // API key is optional — local providers like Ollama do not require one
            $headers[] = "Authorization: Bearer {$this->apikey}";
        }

        $postdata = [
            'model'       => $this->model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user',   'content' => $userMessage],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];

        $api = new ApiCalls();
        $api->setTimeout(120);
        $response = $api->makePostCall($url, $postdata, $headers);

        if (!$response) {
            $this->error = 'Failed to communicate with AI provider: ' . $api->getError();
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->error = $data['error']['message'] ?? 'AI provider returned an error.';
            return false;
        }

        if (empty($data['choices'][0]['message']['content'])) {
            $this->error = 'AI provider did not return a valid answer.';
            return false;
        }

        return trim($data['choices'][0]['message']['content']);
    }

    public function listModels(): array|false
    {
        $url     = $this->baseUrl . '/models';
        $headers = [];
        if (!empty($this->apikey)) {
            $headers[] = "Authorization: Bearer {$this->apikey}";
        }

        $api = new ApiCalls();
        $api->setTimeout(30);
        $response = $api->makeGetCall($url, $headers);

        if (!$response) {
            $this->error = 'Failed to fetch model list: ' . $api->getError();
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->error = $data['error']['message'] ?? 'Provider returned an error.';
            return false;
        }

        // OpenAI format: { "object": "list", "data": [ { "id": "...", ... }, ... ] }
        if (!isset($data['data']) || !is_array($data['data'])) {
            $this->error = 'Provider did not return a valid model list.';
            return false;
        }

        $models = [];
        foreach ($data['data'] as $entry) {
            if (!empty($entry['id'])) {
                $models[] = ['id' => $entry['id']];
            }
        }

        usort($models, fn($a, $b) => strcmp($a['id'], $b['id']));

        return $models;
    }

    public function getError(): string { return $this->error; }
}

/**
 * Adapter for the Anthropic API (Claude).
 * Endpoint: POST {baseUrl}/messages
 * Requires the anthropic-version header on every request.
 */
class AnthropicAdapter implements AiAdapterInterface
{
    private string $baseUrl;
    private string $model;
    private string $apikey;
    private string $error = '';

    public function __construct(string $baseUrl, string $model, string $apikey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model   = $model;
        $this->apikey  = $apikey;
    }

    public function chat(string $systemMessage, string $userMessage, int $maxTokens, float $temperature): string|false
    {
        $url = $this->baseUrl . '/messages';

        $headers = [
            "x-api-key: {$this->apikey}",
            "anthropic-version: 2023-06-01",  // required by Anthropic on every request
        ];

        $postdata = [
            'model'       => $this->model,
            'system'      => $systemMessage,  // system prompt is a top-level field, not inside messages
            'messages'    => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];

        $api = new ApiCalls();
        $api->setTimeout(120);
        $response = $api->makePostCall($url, $postdata, $headers);

        if (!$response) {
            $this->error = 'Failed to communicate with Anthropic: ' . $api->getError();
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->error = $data['error']['message'] ?? 'Anthropic returned an error.';
            return false;
        }

        if (empty($data['content'][0]['text'])) {
            $this->error = 'Anthropic did not return a valid answer.';
            return false;
        }

        return trim($data['content'][0]['text']);
    }

    public function listModels(): array|false
    {
        $url = $this->baseUrl . '/models';

        $headers = [
            "x-api-key: {$this->apikey}",
            "anthropic-version: 2023-06-01",
        ];

        $api = new ApiCalls();
        $api->setTimeout(30);
        $response = $api->makeGetCall($url, $headers);

        if (!$response) {
            $this->error = 'Failed to fetch model list from Anthropic: ' . $api->getError();
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->error = $data['error']['message'] ?? 'Anthropic returned an error.';
            return false;
        }

        // Anthropic format: { "data": [ { "id": "...", "display_name": "...", ... }, ... ] }
        if (!isset($data['data']) || !is_array($data['data'])) {
            $this->error = 'Anthropic did not return a valid model list.';
            return false;
        }

        $models = [];
        foreach ($data['data'] as $entry) {
            if (!empty($entry['id'])) {
                $models[] = ['id' => $entry['id']];
            }
        }

        // Anthropic returns newest first by default — keep that order
        return $models;
    }

    public function getError(): string { return $this->error; }
}

/**
 * Factory: instantiate the correct adapter from configuration.
 */
class AiAdapter
{
    public static function create(string $adapter, string $baseUrl, string $model, string $apikey): AiAdapterInterface
    {
        return match ($adapter) {
            'anthropic' => new AnthropicAdapter($baseUrl, $model, $apikey),
            default     => new OpenAiAdapter($baseUrl, $model, $apikey),
        };
    }
}
