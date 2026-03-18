<?php

/**
 * AIAssistant module class.
 */
class AIAssistant {

    /**
     * Invoked on module install.
     */
    public function vtlib_handler($moduleName, $eventType) {
        if ($eventType === 'module.postinstall') {
            $this->onInstall();
        } elseif ($eventType === 'module.preuninstall') {
            $this->onUninstall();
        }
    }

    private function onInstall() {
        $db = PearDatabase::getInstance();

        // Create tables
        $db->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id VARCHAR(64) DEFAULT 'default',
            user_id INT NOT NULL,
            role ENUM('user','assistant') NOT NULL,
            message TEXT NOT NULL,
            tool_calls JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tenant_user (tenant_id, user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);

        $db->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_audit_log (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);

        $db->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_action_registry (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_name VARCHAR(128) UNIQUE NOT NULL,
            source ENUM('builtin','generated') DEFAULT 'builtin',
            status ENUM('active','disabled','pending_review') DEFAULT 'active',
            generated_by VARCHAR(255),
            approved_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);

        $db->pquery("CREATE TABLE IF NOT EXISTS vtiger_ai_agent_queue (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
    }

    private function onUninstall() {
        // Keep tables — they contain valuable data.
        // Admin can drop manually if needed.
    }
}
