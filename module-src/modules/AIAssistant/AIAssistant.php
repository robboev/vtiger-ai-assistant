<?php
/*+**********************************************************************************
 * AI Assistant Module v0.1.0
 * Self-evolving AI assistant for vtiger CRM
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class AIAssistant extends CRMEntity {

    const VERSION = '0.1.0';
    const MODULE_NAME = 'AIAssistant';

    // CRMEntity required properties
    public $table_name = 'vtiger_aiassistant';
    public $table_index = 'aiassistantid';
    public $customFieldTable = array();
    public $tab_name = array();
    public $tab_name_index = array();
    public $list_fields = array();
    public $list_fields_name = array();
    public $list_link_field = '';
    public $search_fields = array();
    public $search_fields_name = array();
    public $popup_fields = array();
    public $def_basicsearch_col = '';
    public $def_detailview_recname = '';
    public $mandatory_fields = array();
    public $default_order_by = '';
    public $default_sort_order = 'ASC';

    /**
     * vtlib handler - Module lifecycle events
     */
    public function vtlib_handler($moduleName, $eventType) {
        error_log("[AIAssistant] vtlib_handler: $moduleName, $eventType");

        switch ($eventType) {
            case 'module.postinstall':
                $this->onInstall();
                break;
            case 'module.preuninstall':
                $this->onUninstall();
                break;
            case 'module.enabled':
                break;
            case 'module.disabled':
                break;
        }
    }

    private function onInstall() {
        global $adb;

        self::log('postInstall started - v' . self::VERSION);

        // Create tables
        $adb->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id VARCHAR(64) DEFAULT 'default',
            user_id INT NOT NULL,
            role ENUM('user','assistant') NOT NULL,
            message TEXT NOT NULL,
            tool_calls JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tenant_user (tenant_id, user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", array());

        $adb->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id VARCHAR(64) DEFAULT 'default',
            user_id INT NOT NULL,
            action_name VARCHAR(128) NOT NULL,
            params JSON,
            result JSON,
            status ENUM('success','error','rejected') NOT NULL,
            execution_time_ms INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tenant (tenant_id),
            INDEX idx_action (action_name),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", array());

        $adb->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_action_registry (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_name VARCHAR(128) UNIQUE NOT NULL,
            source ENUM('builtin','generated') DEFAULT 'builtin',
            status ENUM('active','disabled','pending_review') DEFAULT 'active',
            generated_by VARCHAR(255),
            approved_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", array());

        $adb->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_agent_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id VARCHAR(64) DEFAULT 'default',
            user_id INT NOT NULL,
            requested_action VARCHAR(255) NOT NULL,
            params JSON,
            status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
            result_action VARCHAR(128),
            error_message TEXT,
            started_at DATETIME,
            completed_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_tenant (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", array());

        self::log('All tables created successfully');
    }

    private function onUninstall() {
        // Keep tables — they contain valuable data.
        // Admin can drop manually if needed.
        self::log('Module uninstalled (tables preserved)');
    }

    /**
     * Log message
     */
    private static function log($message) {
        $logFile = 'logs/aiassistant.log';
        $timestamp = date('Y-m-d H:i:s');
        error_log("[AIAssistant] $message");
        @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
