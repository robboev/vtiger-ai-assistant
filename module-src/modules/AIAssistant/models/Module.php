<?php
/*+**********************************************************************************
 * AI Assistant Module Model
 * Adds Settings link in Module Manager dropdown
 ************************************************************************************/

class AIAssistant_Module_Model extends Vtiger_Module_Model {

    public function getSettingLinks() {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser->isAdminUser()) {
            return array();
        }

        $settingsLinks = array();

        // AI Assistant Settings link
        $settingsLinks[] = array(
            'linktype' => 'LISTVIEWSETTING',
            'linklabel' => 'AI Assistant Settings',
            'linkurl' => 'index.php?module=AIAssistant&view=Settings',
            'linkicon' => ''
        );

        // Uninstall link
        $uninstallUrl = 'javascript:if(confirm("Are you sure you want to uninstall AI Assistant? Database tables will be preserved.")){window.location.href="index.php?module=AIAssistant&action=Uninstall"}';
        $settingsLinks[] = array(
            'linktype' => 'LISTVIEWSETTING',
            'linklabel' => 'LBL_UNINSTALL_MODULE',
            'linkurl' => $uninstallUrl,
            'linkicon' => ''
        );

        return $settingsLinks;
    }

    public function isSummaryViewSupported() {
        return false;
    }

    public function isWorkflowSupported() {
        return false;
    }
}
