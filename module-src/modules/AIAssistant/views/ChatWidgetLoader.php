<?php
/*+**********************************************************************************
 * Chat Widget Loader
 * Called via AJAX/script to inject the chat widget HTML into the page.
 * Loaded by the JS snippet added to Footer.tpl
 ************************************************************************************/

class AIAssistant_ChatWidgetLoader_View extends Vtiger_View_Controller {

    public function checkPermission(Vtiger_Request $request) {
        // Any logged-in user can load the widget
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
                return; // disabled, don't render widget
            }
        }

        $viewer = $this->getViewer($request);
        $viewer->view('ChatWidget.tpl', 'AIAssistant');
    }

    public function getHeaderScripts(Vtiger_Request $request) {
        return array();
    }

    public function getHeaderCss(Vtiger_Request $request) {
        return array();
    }
}
