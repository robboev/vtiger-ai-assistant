<?php

require_once __DIR__ . '/ActionRegistry.php';
require_once __DIR__ . '/ActionExecutor.php';
require_once __DIR__ . '/AgentQueue.php';

/**
 * Chat API endpoint. Handles user messages, sends to Claude API with
 * available tools, executes tool calls, returns responses.
 */
class AIAssistant_ApiEndpoint {

    /** @var AIAssistant_ActionRegistry */
    private $registry;

    /** @var AIAssistant_ActionExecutor */
    private $executor;

    /** @var AIAssistant_AgentQueue */
    private $queue;

    /** @var PearDatabase */
    private $db;

    /** @var string */
    private $tenantId;

    /** @var int */
    private $userId;

    /** @var string Claude API key */
    private $apiKey;

    /** @var string Claude model to use */
    private $model;

    /** Max conversation history to send */
    const MAX_HISTORY = 20;

    public function __construct(string $tenantId, int $userId, string $apiKey, string $model = 'claude-sonnet-4-6') {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->db = PearDatabase::getInstance();
        $this->registry = new AIAssistant_ActionRegistry();
        $this->executor = new AIAssistant_ActionExecutor($tenantId, $userId);
        $this->queue = new AIAssistant_AgentQueue($tenantId);
    }

    /**
     * Handle a user chat message. Returns assistant response.
     */
    public function handleMessage(string $userMessage): array {
        // Input validation
        if (strlen($userMessage) > 2000) {
            return ['role' => 'assistant', 'content' => 'Message too long (max 2000 characters).'];
        }

        if (trim($userMessage) === '') {
            return ['role' => 'assistant', 'content' => 'Please type a message.'];
        }

        // Save user message to conversation history
        $this->saveMessage('user', $userMessage);

        // Build context about the CRM state
        $crmContext = $this->getCrmContext();

        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($crmContext);

        // Get conversation history
        $history = $this->getConversationHistory();

        // Call Claude API
        $response = $this->callClaude($systemPrompt, $history);

        if ($response === null) {
            $msg = "I'm having trouble connecting right now. Please try again in a moment.";
            $this->saveMessage('assistant', $msg);
            return ['role' => 'assistant', 'content' => $msg];
        }

        // Process response - handle tool calls if any
        $finalResponse = $this->processResponse($response);

        // Save assistant response
        $this->saveMessage('assistant', $finalResponse['content'], $finalResponse['tool_calls'] ?? null);

        return $finalResponse;
    }

    /**
     * Build the system prompt with CRM context and available tools.
     */
    private function buildSystemPrompt(array $crmContext): string {
        $prompt = file_get_contents(__DIR__ . '/system_prompt.txt');

        // Inject CRM context
        $contextStr = json_encode($crmContext, JSON_PRETTY_PRINT);
        $prompt .= "\n\n## Current CRM State\n```json\n$contextStr\n```\n";

        return $prompt;
    }

    /**
     * Gather CRM state for context.
     */
    private function getCrmContext(): array {
        $context = [
            'tenant_id' => $this->tenantId,
            'modules' => [],
            'workflows_count' => 0,
            'recent_activity' => [],
        ];

        // Get module record counts
        $modules = ['Leads', 'Contacts', 'Accounts', 'Potentials', 'HelpDesk', 'Products'];
        foreach ($modules as $module) {
            try {
                $moduleModel = Vtiger_Module_Model::getInstance($module);
                if ($moduleModel) {
                    $result = $this->db->pquery(
                        "SELECT COUNT(*) as cnt FROM {$moduleModel->get('basetable')} WHERE 1",
                        []
                    );
                    $count = $result ? (int)$this->db->query_result($result, 0, 'cnt') : 0;
                    $context['modules'][$module] = $count;
                }
            } catch (Exception $e) {
                $context['modules'][$module] = 'N/A';
            }
        }

        // Get workflow count
        try {
            $result = $this->db->pquery("SELECT COUNT(*) as cnt FROM com_vtiger_workflows", []);
            $context['workflows_count'] = $result ? (int)$this->db->query_result($result, 0, 'cnt') : 0;
        } catch (Exception $e) {
            // ignore
        }

        return $context;
    }

    /**
     * Get recent conversation history for context.
     */
    private function getConversationHistory(): array {
        $result = $this->db->pquery(
            "SELECT role, message FROM vtiger_ai_conversations
             WHERE tenant_id = ? AND user_id = ?
             ORDER BY created_at DESC LIMIT ?",
            [$this->tenantId, $this->userId, self::MAX_HISTORY]
        );

        $messages = [];
        if ($result) {
            while ($row = $this->db->fetch_array($result)) {
                $messages[] = [
                    'role' => $row['role'],
                    'content' => $row['message'],
                ];
            }
        }

        // Reverse to chronological order
        return array_reverse($messages);
    }

    /**
     * Call Claude API with tools.
     */
    private function callClaude(string $systemPrompt, array $messages): ?array {
        $tools = $this->registry->getToolDefinitions();

        // Convert tool definitions to Claude API format
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
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        if (!empty($claudeTools)) {
            $payload['tools'] = $claudeTools;
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            error_log("AIAssistant Claude API error: HTTP $httpCode, response: $response");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Process Claude response - execute tool calls if present.
     */
    private function processResponse(array $response): array {
        $textParts = [];
        $toolCalls = [];

        foreach ($response['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $textParts[] = $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolName = $block['name'];
                $toolInput = $block['input'] ?? [];

                if ($this->registry->hasAction($toolName)) {
                    // Known action - execute it
                    $action = $this->registry->getAction($toolName);
                    $result = $this->executor->execute($action, $toolInput);
                    $toolCalls[] = [
                        'name' => $toolName,
                        'input' => $toolInput,
                        'result' => $result,
                    ];

                    if ($result['success']) {
                        $textParts[] = $result['message'] ?? "Done.";
                    } else {
                        $textParts[] = "Failed: " . ($result['message'] ?? 'Unknown error');
                    }
                } else {
                    // Unknown action - queue for CLI agent
                    $queueResult = $this->queue->enqueue(
                        $toolName,
                        $toolInput,
                        $this->userId
                    );
                    $textParts[] = "I don't have that capability yet, but I've queued it for building. " .
                                   "I'll notify you when it's ready. (Queue #" . ($queueResult['id'] ?? '?') . ")";
                }
            }
        }

        return [
            'role' => 'assistant',
            'content' => implode("\n\n", $textParts),
            'tool_calls' => !empty($toolCalls) ? $toolCalls : null,
        ];
    }

    /**
     * Save a message to conversation history.
     */
    private function saveMessage(string $role, string $message, ?array $toolCalls = null): void {
        $this->db->pquery(
            "INSERT INTO vtiger_ai_conversations (tenant_id, user_id, role, message, tool_calls)
             VALUES (?, ?, ?, ?, ?)",
            [
                $this->tenantId,
                $this->userId,
                $role,
                $message,
                $toolCalls ? json_encode($toolCalls) : null,
            ]
        );
    }
}
