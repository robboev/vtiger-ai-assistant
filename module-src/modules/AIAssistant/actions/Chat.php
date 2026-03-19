<?php

/**
 * vtiger action controller for the chat API endpoint.
 * Handles POST requests from the chat widget.
 */
class AIAssistant_Chat_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser || !$currentUser->getId()) {
            throw new AppException('Login required');
        }
        return true;
    }

    public function process(Vtiger_Request $request) {
        require_once 'modules/AIAssistant/ApiEndpoint.php';

        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUser->getId();

        // Get tenant ID (multi-tenant support)
        $tenantId = 'default';
        if (defined('TENANT_ID')) {
            $tenantId = TENANT_ID;
        }

        // Load config
        $config = self::getConfig();
        if (empty($config['api_key']) && ($config['provider'] ?? 'anthropic') !== 'ollama') {
            $this->sendJsonResponse([
                'role' => 'assistant',
                'content' => 'AI Assistant is not configured. Please ask your administrator to set the API key in Settings.',
            ]);
            return;
        }

        // Check if enabled
        if (!($config['enabled'] ?? true)) {
            $this->sendJsonResponse([
                'role' => 'assistant',
                'content' => 'AI Assistant is currently disabled.',
            ]);
            return;
        }

        // Read JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';

        if (empty($message)) {
            $this->sendJsonResponse([
                'role' => 'assistant',
                'content' => 'Please type a message.',
            ]);
            return;
        }

        // Process through API endpoint
        $endpoint = new AIAssistant_ApiEndpoint($tenantId, $userId, $config);
        $response = $endpoint->handleMessage($message);

        $this->sendJsonResponse($response);
    }

    private function sendJsonResponse(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private static function getConfig(): array {
        $configFile = dirname(__DIR__) . '/config_ai.php';
        if (file_exists($configFile)) {
            return include($configFile);
        }
        return [];
    }
}
