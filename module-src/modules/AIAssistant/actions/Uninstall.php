<?php
/*+**********************************************************************************
 * AI Assistant Uninstall Action
 ************************************************************************************/

class AIAssistant_Uninstall_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser->isAdminUser()) {
            throw new AppException('Admin access required');
        }
        return true;
    }

    public function process(Vtiger_Request $request) {
        $moduleName = 'AIAssistant';

        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        if ($moduleInstance) {
            $moduleInstance->delete();
        }

        header('Location: index.php?module=ModuleManager&parent=Settings&view=List');
        exit;
    }
}
