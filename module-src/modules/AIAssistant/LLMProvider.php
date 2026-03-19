<?php
/*+**********************************************************************************
 * LLM Provider abstraction
 * Supports: Anthropic (Claude), OpenAI (GPT), Ollama (local/free)
 ************************************************************************************/

class AIAssistant_LLMProvider {

    private $provider;
    private $apiKey;
    private $model;
    private $baseUrl;
    private $maxTokens;

    /** Default base URLs per provider */
    private static $DEFAULT_URLS = [
        'anthropic' => 'https://api.anthropic.com',
        'openai'    => 'https://api.openai.com',
        'ollama'    => 'http://localhost:11434',
    ];

    public function __construct(array $config) {
        $this->provider = $config['provider'] ?? 'anthropic';
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'claude-haiku-4-5-20251001';
        $this->baseUrl = $config['api_base_url'] ?: (self::$DEFAULT_URLS[$this->provider] ?? '');
        $this->maxTokens = $config['max_tokens'] ?? 1024;
    }

    /**
     * Send a chat completion request with tools.
     * Returns parsed response or null on error.
     */
    public function chat(string $systemPrompt, array $messages, array $tools = []): ?array {
        switch ($this->provider) {
            case 'anthropic':
                return $this->callAnthropic($systemPrompt, $messages, $tools);
            case 'openai':
                return $this->callOpenAI($systemPrompt, $messages, $tools);
            case 'ollama':
                return $this->callOllama($systemPrompt, $messages, $tools);
            default:
                error_log("AIAssistant: Unknown provider: {$this->provider}");
                return null;
        }
    }

    /**
     * Normalize response to a common format:
     * [['type' => 'text', 'text' => '...'], ['type' => 'tool_use', 'name' => '...', 'input' => [...]]]
     */

    // --- Anthropic (Claude) ---
    private function callAnthropic(string $systemPrompt, array $messages, array $tools): ?array {
        $claudeTools = array_map(function($tool) {
            return [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $tool['parameters']['properties'] ?? [],
                    'required' => $tool['parameters']['required'] ?? [],
                ],
            ];
        }, $tools);

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        if (!empty($claudeTools)) {
            $payload['tools'] = $claudeTools;
        }

        $response = $this->httpPost(
            $this->baseUrl . '/v1/messages',
            $payload,
            [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ]
        );

        if (!$response) return null;

        // Anthropic format is already our normalized format
        return $response;
    }

    // --- OpenAI (GPT) ---
    private function callOpenAI(string $systemPrompt, array $messages, array $tools): ?array {
        // Prepend system message
        $openaiMessages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($messages as $msg) {
            $openaiMessages[] = $msg;
        }

        // Convert tools to OpenAI format
        $openaiTools = [];
        foreach ($tools as $tool) {
            $openaiTools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $tool['parameters']['properties'] ?? [],
                        'required' => $tool['parameters']['required'] ?? [],
                    ],
                ],
            ];
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => $openaiMessages,
        ];

        if (!empty($openaiTools)) {
            $payload['tools'] = $openaiTools;
        }

        $response = $this->httpPost(
            $this->baseUrl . '/v1/chat/completions',
            $payload,
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ]
        );

        if (!$response) return null;

        // Normalize OpenAI response to Anthropic format
        return $this->normalizeOpenAI($response);
    }

    // --- Ollama (local, free) ---
    private function callOllama(string $systemPrompt, array $messages, array $tools): ?array {
        // Ollama uses OpenAI-compatible API
        $ollamaMessages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($messages as $msg) {
            $ollamaMessages[] = $msg;
        }

        $payload = [
            'model' => $this->model,
            'messages' => $ollamaMessages,
            'stream' => false,
        ];

        // Ollama supports tools in newer versions
        if (!empty($tools)) {
            $ollamaTools = [];
            foreach ($tools as $tool) {
                $ollamaTools[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool['name'],
                        'description' => $tool['description'],
                        'parameters' => [
                            'type' => 'object',
                            'properties' => $tool['parameters']['properties'] ?? [],
                            'required' => $tool['parameters']['required'] ?? [],
                        ],
                    ],
                ];
            }
            $payload['tools'] = $ollamaTools;
        }

        $response = $this->httpPost(
            $this->baseUrl . '/api/chat',
            $payload,
            ['Content-Type: application/json'],
            60 // longer timeout for local models
        );

        if (!$response) return null;

        return $this->normalizeOllama($response);
    }

    /**
     * Normalize OpenAI response to Anthropic-like format.
     */
    private function normalizeOpenAI(array $response): array {
        $choice = $response['choices'][0] ?? null;
        if (!$choice) return ['content' => [['type' => 'text', 'text' => 'No response.']]];

        $msg = $choice['message'];
        $content = [];

        // Text content
        if (!empty($msg['content'])) {
            $content[] = ['type' => 'text', 'text' => $msg['content']];
        }

        // Tool calls
        if (!empty($msg['tool_calls'])) {
            foreach ($msg['tool_calls'] as $tc) {
                $content[] = [
                    'type' => 'tool_use',
                    'name' => $tc['function']['name'],
                    'input' => json_decode($tc['function']['arguments'], true) ?? [],
                ];
            }
        }

        return ['content' => $content];
    }

    /**
     * Normalize Ollama response to Anthropic-like format.
     */
    private function normalizeOllama(array $response): array {
        $msg = $response['message'] ?? null;
        if (!$msg) return ['content' => [['type' => 'text', 'text' => 'No response.']]];

        $content = [];

        if (!empty($msg['content'])) {
            $content[] = ['type' => 'text', 'text' => $msg['content']];
        }

        // Ollama tool calls (newer versions)
        if (!empty($msg['tool_calls'])) {
            foreach ($msg['tool_calls'] as $tc) {
                $fn = $tc['function'] ?? [];
                $content[] = [
                    'type' => 'tool_use',
                    'name' => $fn['name'] ?? '',
                    'input' => is_string($fn['arguments'] ?? null) ? json_decode($fn['arguments'], true) : ($fn['arguments'] ?? []),
                ];
            }
        }

        return ['content' => $content];
    }

    /**
     * HTTP POST helper.
     */
    private function httpPost(string $url, array $payload, array $headers, int $timeout = 30): ?array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            error_log("AIAssistant LLM error [{$this->provider}]: HTTP $httpCode, curl: $error, response: " . substr($response ?: '', 0, 500));
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get provider name for display.
     */
    public function getProviderName(): string {
        return $this->provider;
    }

    /**
     * Get model name for display.
     */
    public function getModelName(): string {
        return $this->model;
    }
}
