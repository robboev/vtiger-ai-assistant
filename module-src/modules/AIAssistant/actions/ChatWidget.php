<?php
/**
 * Outputs ONLY the chat widget HTML (no vtiger page wrapper).
 * Called via AJAX by ChatWidgetLoader.js.
 * Rejects direct browser requests — AJAX only.
 */
class AIAssistant_ChatWidget_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        // Reject non-AJAX requests — prevent direct URL access
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            header('Location: index.php');
            exit;
        }

        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser || !$currentUser->getId()) {
            throw new AppException('Login required');
        }
        return true;
    }

    public function process(Vtiger_Request $request) {
        // Check if module is enabled
        $configFile = 'modules/AIAssistant/config_ai.php';
        if (file_exists($configFile)) {
            $config = include($configFile);
            if (!($config['enabled'] ?? true)) {
                return;
            }
        }

        // Output raw template file — bypass Smarty to avoid vtiger layout wrapper
        $tplPath = 'layouts/v7/modules/AIAssistant/ChatWidget.tpl';
        if (file_exists($tplPath)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($tplPath);
        }
    }
}
