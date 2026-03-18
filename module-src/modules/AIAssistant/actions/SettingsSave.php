<?php

/**
 * Admin action: save settings, approve/disable actions.
 */
class AIAssistant_SettingsSave_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser->isAdminUser()) {
            throw new AppException('Admin access required');
        }
        return true;
    }

    public function process(Vtiger_Request $request) {
        $operation = $request->get('operation');
        $db = PearDatabase::getInstance();

        switch ($operation) {
            case 'save_config':
                $this->saveConfig($request);
                break;

            case 'approve_action':
                $actionName = $request->get('action_name');
                $currentUser = Users_Record_Model::getCurrentUserModel();
                $db->pquery(
                    "UPDATE vtiger_ai_action_registry SET status='active', approved_by=? WHERE action_name=?",
                    [$currentUser->getId(), $actionName]
                );
                break;

            case 'disable_action':
                $actionName = $request->get('action_name');
                $db->pquery(
                    "UPDATE vtiger_ai_action_registry SET status='disabled' WHERE action_name=?",
                    [$actionName]
                );
                break;

            case 'enable_action':
                $actionName = $request->get('action_name');
                $db->pquery(
                    "UPDATE vtiger_ai_action_registry SET status='active' WHERE action_name=?",
                    [$actionName]
                );
                break;

            case 'clear_queue':
                $db->pquery(
                    "DELETE FROM vtiger_ai_agent_queue WHERE status IN ('completed', 'failed')", []
                );
                break;

            case 'retry_queue':
                $queueId = $request->get('queue_id');
                $db->pquery(
                    "UPDATE vtiger_ai_agent_queue SET status='pending', error_message=NULL, started_at=NULL WHERE id=?",
                    [$queueId]
                );
                break;
        }

        header('Location: index.php?module=AIAssistant&view=Settings&tab=' . ($request->get('tab') ?: 'actions'));
        exit;
    }

    private function saveConfig(Vtiger_Request $request) {
        $apiKey = $request->get('api_key');
        $model = $request->get('model') ?: 'claude-sonnet-4-6';
        $rateLimit = (int)($request->get('rate_limit') ?: 100);
        $enabled = $request->get('enabled') ? true : false;

        $configFile = dirname(__DIR__) . '/config_ai.php';

        // Read existing config
        $config = file_exists($configFile) ? include($configFile) : [];

        // Only update API key if provided (don't clear existing)
        if (!empty($apiKey)) {
            $config['anthropic_api_key'] = $apiKey;
        }

        $config['model'] = $model;
        $config['rate_limit'] = $rateLimit;
        $config['enabled'] = $enabled;

        // Write config
        $content = "<?php\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($configFile, $content);
    }
}
