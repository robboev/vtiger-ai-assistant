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

        // Get API key from module config
        $apiKey = self::getApiKey();
        if (!$apiKey) {
            $this->sendJsonResponse([
                'role' => 'assistant',
                'content' => 'AI Assistant is not configured. Please ask your administrator to set the API key.',
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
        $endpoint = new AIAssistant_ApiEndpoint($tenantId, $userId, $apiKey);
        $response = $endpoint->handleMessage($message);

        $this->sendJsonResponse($response);
    }

    private function sendJsonResponse(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private static function getApiKey(): ?string {
        $configFile = __DIR__ . '/../../config_ai.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            return $config['anthropic_api_key'] ?? null;
        }
        return null;
    }
}
