<?php

require_once __DIR__ . '/Base.php';

/**
 * Read-only action: lists existing workflows.
 */
class AIAction_ListWorkflows extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'list_workflows',
            'description' => 'List existing workflows, optionally filtered by module.',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Filter by module name (optional)',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $params): array {
        $module = $params['module'] ?? null;

        $query = "SELECT workflow_id, module_name, summary, execution_condition, defaultworkflow
                  FROM com_vtiger_workflows";
        $queryParams = [];

        if ($module) {
            $query .= " WHERE module_name = ?";
            $queryParams[] = $module;
        }

        $query .= " ORDER BY workflow_id DESC LIMIT 50";

        $result = $this->db->pquery($query, $queryParams);

        $workflows = [];
        $triggerNames = [1 => 'on_create', 2 => 'on_every_save', 3 => 'on_modify', 6 => 'on_schedule'];

        if ($result) {
            while ($row = $this->db->fetch_array($result)) {
                $workflows[] = [
                    'id' => $row['workflow_id'],
                    'module' => $row['module_name'],
                    'description' => $row['summary'],
                    'trigger' => $triggerNames[(int)$row['execution_condition']] ?? 'unknown',
                    'is_default' => (bool)$row['defaultworkflow'],
                ];
            }
        }

        $count = count($workflows);
        $message = $count === 0
            ? "No workflows found" . ($module ? " for $module" : "") . ". Want me to create one?"
            : "Found $count workflow(s)" . ($module ? " for $module" : "") . ".";

        return [
            'success' => true,
            'message' => $message,
            'workflows' => $workflows,
        ];
    }
}
