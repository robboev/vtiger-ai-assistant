<?php

/**
 * Event handler that injects the AI chat widget into vtiger pages.
 * Hooks into vtiger.view.head.after to add CSS/JS.
 */
class AIAssistantHandler extends VTEventHandler {

    public function handleEvent($eventName, $data) {
        if ($eventName === 'vtiger.view.head.after') {
            $this->injectChatWidget();
        }
    }

    private function injectChatWidget() {
        global $current_user;

        if (!$current_user || !$current_user->id) {
            return;
        }

        // Check if AI Assistant is enabled for this user/tenant
        $viewer = Vtiger_Viewer::getInstance();
        $viewer->assign('AI_ASSISTANT_ENABLED', true);
        $viewer->assign('AI_ASSISTANT_USER_ID', $current_user->id);
    }
}
