<?php

require_once __DIR__ . '/actions/Base.php';

/**
 * Sandboxed action executor with safety limits.
 * All action execution goes through this class.
 */
class AIAssistant_ActionExecutor {

    /** @var PearDatabase */
    private $db;

    /** @var string Tenant ID for audit logging */
    private $tenantId;

    /** @var int Current user ID */
    private $userId;

    /** Rate limit: max executions per user per hour */
    const RATE_LIMIT_PER_HOUR = 100;

    public function __construct(string $tenantId = 'default', int $userId = 0) {
        $this->db = PearDatabase::getInstance();
        $this->tenantId = $tenantId;
        $this->userId = $userId;
    }

    /**
     * Execute an action with full safety checks.
     */
    public function execute(AIAction_Base $action, array $params): array {
        $actionDef = $action::definition();
        $actionName = $actionDef['name'];
        $startTime = microtime(true);

        try {
            // 1. Rate limit check
            if ($this->isRateLimited()) {
                return $this->reject($actionName, $params, 'Rate limit exceeded (max ' . self::RATE_LIMIT_PER_HOUR . '/hour)');
            }

            // 2. Validate parameters
            $validationErrors = $action->validateParams($params);
            if (!empty($validationErrors)) {
                return $this->reject($actionName, $params, 'Validation failed: ' . implode(', ', $validationErrors));
            }

            // 3. Record limit check
            if (isset($params['record_ids']) && is_array($params['record_ids'])) {
                if (count($params['record_ids']) > $action->maxRecords()) {
                    return $this->reject($actionName, $params, "Batch limit: max {$action->maxRecords()} records");
                }
            }

            // 4. Set time limit
            $previousLimit = ini_get('max_execution_time');
            set_time_limit($action->maxExecutionTime());

            // 5. Execute
            $result = $action->execute($params);

            // 6. Restore time limit
            set_time_limit($previousLimit);

            // 7. Log success
            $executionMs = (int)((microtime(true) - $startTime) * 1000);
            $this->audit($actionName, $params, $result, 'success', $executionMs);

            return $result;

        } catch (Exception $e) {
            $executionMs = (int)((microtime(true) - $startTime) * 1000);
            $errorResult = [
                'success' => false,
                'message' => 'Action failed: ' . $e->getMessage(),
            ];
            $this->audit($actionName, $params, $errorResult, 'error', $executionMs);

            return $errorResult;
        }
    }

    /**
     * Check if current user has exceeded rate limit.
     */
    private function isRateLimited(): bool {
        $result = $this->db->pquery(
            "SELECT COUNT(*) as cnt FROM vtiger_ai_audit_log
             WHERE tenant_id = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$this->tenantId, $this->userId]
        );

        if ($result && $this->db->num_rows($result) > 0) {
            $count = (int)$this->db->query_result($result, 0, 'cnt');
            return $count >= self::RATE_LIMIT_PER_HOUR;
        }

        return false;
    }

    /**
     * Reject an action and log it.
     */
    private function reject(string $actionName, array $params, string $reason): array {
        $result = ['success' => false, 'message' => $reason];
        $this->audit($actionName, $params, $result, 'rejected', 0);
        return $result;
    }

    /**
     * Write audit log entry.
     */
    private function audit(string $actionName, array $params, array $result, string $status, int $executionMs): void {
        try {
            $this->db->pquery(
                "INSERT INTO vtiger_ai_audit_log (tenant_id, user_id, action_name, params, result, status, execution_time_ms)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $this->tenantId,
                    $this->userId,
                    $actionName,
                    json_encode($params),
                    json_encode($result),
                    $status,
                    $executionMs,
                ]
            );
        } catch (Exception $e) {
            error_log("AIAssistant audit log failed: " . $e->getMessage());
        }
    }
}
