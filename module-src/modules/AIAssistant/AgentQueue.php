<?php

/**
 * Queue for requests that don't have a matching action.
 * CLI agent picks these up and builds new action classes.
 */
class AIAssistant_AgentQueue {

    /** @var PearDatabase */
    private $db;

    /** @var string */
    private $tenantId;

    /** Queue table name */
    const TABLE = 'vtiger_ai_agent_queue';

    public function __construct(string $tenantId = 'default') {
        $this->db = PearDatabase::getInstance();
        $this->tenantId = $tenantId;
    }

    /**
     * Add a request to the queue for CLI agent processing.
     */
    public function enqueue(string $requestedAction, array $params, int $userId): array {
        $this->db->pquery(
            "INSERT INTO " . self::TABLE . " (tenant_id, user_id, requested_action, params, status)
             VALUES (?, ?, ?, ?, 'pending')",
            [
                $this->tenantId,
                $userId,
                $requestedAction,
                json_encode($params),
            ]
        );

        $id = $this->db->getLastInsertID();

        return [
            'success' => true,
            'id' => $id,
            'status' => 'pending',
        ];
    }

    /**
     * Get pending items for CLI agent to process.
     */
    public function getPending(int $limit = 10): array {
        $result = $this->db->pquery(
            "SELECT * FROM " . self::TABLE . " WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?",
            [$limit]
        );

        $items = [];
        if ($result) {
            while ($row = $this->db->fetch_array($result)) {
                $row['params'] = json_decode($row['params'], true);
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * Mark a queue item as being processed.
     */
    public function markProcessing(int $id): bool {
        $result = $this->db->pquery(
            "UPDATE " . self::TABLE . " SET status = 'processing', started_at = NOW() WHERE id = ?",
            [$id]
        );
        return $result !== false;
    }

    /**
     * Mark a queue item as completed.
     */
    public function markCompleted(int $id, string $actionName): bool {
        $result = $this->db->pquery(
            "UPDATE " . self::TABLE . " SET status = 'completed', result_action = ?, completed_at = NOW() WHERE id = ?",
            [$actionName, $id]
        );
        return $result !== false;
    }

    /**
     * Mark a queue item as failed.
     */
    public function markFailed(int $id, string $reason): bool {
        $result = $this->db->pquery(
            "UPDATE " . self::TABLE . " SET status = 'failed', error_message = ? WHERE id = ?",
            [$reason, $id]
        );
        return $result !== false;
    }
}
