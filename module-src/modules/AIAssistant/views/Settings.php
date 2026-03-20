<?php

/**
 * Admin settings view for AI Assistant.
 * Shows config, action registry, audit log, and agent queue.
 */
class AIAssistant_Settings_View extends Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $db = PearDatabase::getInstance();
        $tab = $request->get('tab') ?: 'actions';

        // Get actions
        $actions = [];
        $result = $db->pquery(
            "SELECT * FROM vtiger_ai_action_registry ORDER BY created_at DESC", []
        );
        if ($result) {
            while ($row = $db->fetch_array($result)) {
                $actions[] = $row;
            }
        }

        // Get built-in actions (from filesystem)
        $actionsDir = __DIR__ . '/../actions';
        $builtinFiles = glob($actionsDir . '/*.php');
        $builtinActions = [];
        foreach ($builtinFiles as $file) {
            $name = basename($file, '.php');
            if (in_array($name, ['Base', 'Chat', 'SettingsSave', 'Uninstall'])) continue;
            $builtinActions[] = $name;
        }

        // Get audit log (last 100)
        $auditLog = [];
        $result = $db->pquery(
            "SELECT al.*, u.user_name
             FROM vtiger_ai_audit_log al
             LEFT JOIN vtiger_users u ON al.user_id = u.id
             ORDER BY al.created_at DESC LIMIT 100", []
        );
        if ($result) {
            while ($row = $db->fetch_array($result)) {
                $auditLog[] = $row;
            }
        }

        // Get agent queue
        $queue = [];
        $result = $db->pquery(
            "SELECT q.*, u.user_name
             FROM vtiger_ai_agent_queue q
             LEFT JOIN vtiger_users u ON q.user_id = u.id
             ORDER BY q.created_at DESC LIMIT 50", []
        );
        if ($result) {
            while ($row = $db->fetch_array($result)) {
                $queue[] = $row;
            }
        }

        // Load current config
        $configFile = __DIR__ . '/../config_ai.php';
        $configExists = file_exists($configFile);
        $currentConfig = $configExists ? include($configFile) : [];

        $viewer->assign('TAB', $tab);
        $viewer->assign('ACTIONS', $actions);
        $viewer->assign('BUILTIN_ACTIONS', $builtinActions);
        $viewer->assign('AUDIT_LOG', $auditLog);
        $viewer->assign('QUEUE', $queue);
        $viewer->assign('CONFIG_EXISTS', $configExists);
        $viewer->assign('CURRENT_PROVIDER', $currentConfig['provider'] ?? 'anthropic');
        $viewer->assign('API_KEY_SET', !empty($currentConfig['api_key'] ?? ''));
        $viewer->assign('CURRENT_MODEL', $currentConfig['model'] ?? 'claude-haiku-4-5-20251001');
        $viewer->assign('API_BASE_URL', $currentConfig['api_base_url'] ?? '');
        $viewer->assign('RATE_LIMIT', $currentConfig['rate_limit'] ?? 100);
        $viewer->assign('MODULE', 'AIAssistant');

        $viewer->view('Settings.tpl', 'AIAssistant');
    }
}
