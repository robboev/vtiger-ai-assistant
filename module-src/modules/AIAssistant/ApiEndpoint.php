<?php

require_once __DIR__ . '/ActionRegistry.php';
require_once __DIR__ . '/ActionExecutor.php';
require_once __DIR__ . '/AgentQueue.php';
require_once __DIR__ . '/LLMProvider.php';

/**
 * Chat API endpoint. Handles user messages, sends to LLM provider with
 * available tools, executes tool calls, returns responses.
 */
class AIAssistant_ApiEndpoint {

    /** @var AIAssistant_ActionRegistry */
    private $registry;

    /** @var AIAssistant_ActionExecutor */
    private $executor;

    /** @var AIAssistant_AgentQueue */
    private $queue;

    /** @var AIAssistant_LLMProvider */
    private $llm;

    /** @var PearDatabase */
    private $db;

    /** @var string */
    private $tenantId;

    /** @var int */
    private $userId;

    /** Max conversation history to send */
    const MAX_HISTORY = 20;

    public function __construct(string $tenantId, int $userId, array $config) {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->db = PearDatabase::getInstance();
        $this->llm = new AIAssistant_LLMProvider($config);
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

        // Get tool definitions
        $tools = $this->registry->getToolDefinitions();

        // Call LLM
        $response = $this->llm->chat($systemPrompt, $history, $tools);

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
     * Build the system prompt with CRM context.
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
            'user_id' => $this->userId,
            'today' => date('Y-m-d'),
            'modules' => [],
            'workflows_count' => 0,
        ];

        // Get current user info
        global $current_user;
        if ($current_user) {
            $context['user_name'] = $current_user->column_fields['first_name'] . ' ' . $current_user->column_fields['last_name'];
            $context['user_language'] = $current_user->language ?? 'en_us';
        }

        // Get module record counts
        $modules = ['Leads', 'Contacts', 'Accounts', 'Potentials', 'HelpDesk', 'Products', 'Quotes', 'Invoice', 'SalesOrder', 'Campaigns'];
        foreach ($modules as $module) {
            try {
                $moduleModel = Vtiger_Module_Model::getInstance($module);
                if ($moduleModel) {
                    $baseTable = $moduleModel->get('basetable');
                    if ($baseTable) {
                        $result = $this->db->pquery("SELECT COUNT(*) as cnt FROM $baseTable", []);
                        $count = $result ? (int)$this->db->query_result($result, 0, 'cnt') : 0;
                        $context['modules'][$module] = $count;
                    }
                }
            } catch (Exception $e) {
                // module not installed, skip
            }
        }

        // Get workflow count
        try {
            $result = $this->db->pquery("SELECT COUNT(*) as cnt FROM com_vtiger_workflows WHERE defaultworkflow = 0", []);
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

        return array_reverse($messages);
    }

    /**
     * Process LLM response - execute tool calls if present.
     */
    private function processResponse(array $response): array {
        $textParts = [];
        $toolCalls = [];
        $uiAction = null;

        foreach ($response['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $textParts[] = $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolName = $block['name'];
                $toolInput = $block['input'] ?? [];

                if ($this->registry->hasAction($toolName)) {
                    $action = $this->registry->getAction($toolName);
                    $result = $this->executor->execute($action, $toolInput);
                    $toolCalls[] = [
                        'name' => $toolName,
                        'input' => $toolInput,
                        'result' => $result,
                    ];

                    // Check for UI action in result
                    if (!empty($result['ui_action'])) {
                        $uiAction = $result['ui_action'];
                    }

                    if ($result['success']) {
                        $textParts[] = $result['message'] ?? "Done.";
                    } else {
                        $textParts[] = "Failed: " . ($result['message'] ?? 'Unknown error');
                    }
                } else {
                    $queueResult = $this->queue->enqueue($toolName, $toolInput, $this->userId);
                    $textParts[] = "I don't have that capability yet, but I've queued it for building. " .
                                   "I'll notify you when it's ready. (Queue #" . ($queueResult['id'] ?? '?') . ")";
                }
            }
        }

        $result = [
            'role' => 'assistant',
            'content' => implode("\n\n", $textParts),
            'tool_calls' => !empty($toolCalls) ? $toolCalls : null,
        ];

        if ($uiAction) {
            $result['ui_action'] = $uiAction;
        }

        return $result;
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
