<?php

require_once __DIR__ . '/actions/Base.php';

/**
 * Discovers, loads, and manages all available AI actions.
 * Scans the actions/ directory for classes extending AIAction_Base.
 */
class AIAssistant_ActionRegistry {

    /** @var array<string, AIAction_Base> Loaded action instances */
    private $actions = [];

    /** @var array<string, array> Tool definitions for Claude API */
    private $toolDefinitions = [];

    /** @var PearDatabase */
    private $db;

    /** @var string Path to actions directory */
    private $actionsDir;

    public function __construct(string $actionsDir = null) {
        $this->db = PearDatabase::getInstance();
        $this->actionsDir = $actionsDir ?: __DIR__ . '/actions';
        $this->discover();
    }

    /**
     * Scan actions directory and load all valid action classes.
     */
    private function discover(): void {
        $files = glob($this->actionsDir . '/*.php');

        foreach ($files as $file) {
            $basename = basename($file, '.php');

            // Skip base class
            if ($basename === 'Base') continue;

            $className = 'AIAction_' . $basename;

            // Check if action is disabled in DB
            if ($this->isDisabled($basename)) continue;

            require_once $file;

            if (!class_exists($className)) continue;

            $reflection = new ReflectionClass($className);
            if (!$reflection->isSubclassOf('AIAction_Base') || $reflection->isAbstract()) {
                continue;
            }

            try {
                $instance = new $className();
                $definition = $className::definition();

                $this->actions[$definition['name']] = $instance;
                $this->toolDefinitions[$definition['name']] = $definition;
            } catch (Exception $e) {
                error_log("AIAssistant: Failed to load action $className: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if an action is disabled in the registry table.
     */
    private function isDisabled(string $actionName): bool {
        $result = $this->db->pquery(
            "SELECT status FROM vtiger_ai_action_registry WHERE action_name = ?",
            [$actionName]
        );

        if ($result && $this->db->num_rows($result) > 0) {
            $status = $this->db->query_result($result, 0, 'status');
            return $status !== 'active';
        }

        // Not in DB = allowed (built-in actions don't need DB entry)
        return false;
    }

    /**
     * Check if an action exists and is loaded.
     */
    public function hasAction(string $name): bool {
        return isset($this->actions[$name]);
    }

    /**
     * Get an action instance by name.
     */
    public function getAction(string $name): ?AIAction_Base {
        return $this->actions[$name] ?? null;
    }

    /**
     * Get all tool definitions for Claude API system prompt.
     */
    public function getToolDefinitions(): array {
        return array_values($this->toolDefinitions);
    }

    /**
     * Get list of all loaded action names.
     */
    public function getActionNames(): array {
        return array_keys($this->actions);
    }

    /**
     * Register a newly generated action (from CLI agent).
     * Validates source code before registration.
     */
    public function registerGeneratedAction(string $filePath, string $generatedBy = 'cli-agent'): array {
        // Step 1: Validate source code
        $violations = AIAction_Base::validateSourceCode($filePath);
        if (!empty($violations)) {
            return [
                'success' => false,
                'errors' => $violations,
            ];
        }

        // Step 2: Load and validate class
        $basename = basename($filePath, '.php');
        $className = 'AIAction_' . $basename;

        require_once $filePath;

        if (!class_exists($className)) {
            return ['success' => false, 'errors' => ["Class $className not found in file"]];
        }

        $reflection = new ReflectionClass($className);
        if (!$reflection->isSubclassOf('AIAction_Base')) {
            return ['success' => false, 'errors' => ['Must extend AIAction_Base']];
        }

        // Step 3: Register in DB as pending_review
        $definition = $className::definition();
        $this->db->pquery(
            "INSERT INTO vtiger_ai_action_registry (action_name, source, status, generated_by)
             VALUES (?, 'generated', 'pending_review', ?)
             ON DUPLICATE KEY UPDATE status='pending_review', generated_by=?",
            [$definition['name'], $generatedBy, $generatedBy]
        );

        return [
            'success' => true,
            'action_name' => $definition['name'],
            'status' => 'pending_review',
        ];
    }

    /**
     * Approve a pending action (admin function).
     */
    public function approveAction(string $actionName, int $approvedBy): bool {
        $result = $this->db->pquery(
            "UPDATE vtiger_ai_action_registry SET status='active', approved_by=? WHERE action_name=?",
            [$approvedBy, $actionName]
        );
        return $result !== false;
    }

    /**
     * Disable an action (kill switch).
     */
    public function disableAction(string $actionName): bool {
        $result = $this->db->pquery(
            "UPDATE vtiger_ai_action_registry SET status='disabled' WHERE action_name=?",
            [$actionName]
        );
        return $result !== false;
    }
}
