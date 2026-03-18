<?php

require_once __DIR__ . '/Base.php';

/**
 * Read-only action: returns record counts and basic stats for modules.
 */
class AIAction_GetModuleStats extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'get_module_stats',
            'description' => 'Get record counts and basic statistics for CRM modules. Use this to understand the current state of the CRM.',
            'parameters' => [
                'properties' => [
                    'modules' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'List of module names to get stats for. If empty, returns all major modules.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $params): array {
        $requestedModules = $params['modules'] ?? [
            'Leads', 'Contacts', 'Accounts', 'Potentials',
            'HelpDesk', 'Products', 'Quotes', 'Invoice',
        ];

        $stats = [];
        foreach ($requestedModules as $moduleName) {
            $module = $this->getModule($moduleName);
            if (!$module) continue;

            $baseTable = $module->get('basetable');
            if (!$baseTable) continue;

            $result = $this->db->pquery("SELECT COUNT(*) as cnt FROM $baseTable", []);
            $count = $result ? (int)$this->db->query_result($result, 0, 'cnt') : 0;

            $stats[$moduleName] = [
                'count' => $count,
                'status' => $count === 0 ? 'empty' : ($count < 10 ? 'getting_started' : 'active'),
            ];
        }

        // Get workflow count
        $wfResult = $this->db->pquery("SELECT COUNT(*) as cnt FROM com_vtiger_workflows WHERE defaultworkflow = 0", []);
        $customWorkflows = $wfResult ? (int)$this->db->query_result($wfResult, 0, 'cnt') : 0;

        $emptyModules = array_keys(array_filter($stats, fn($s) => $s['status'] === 'empty'));

        $summary = count($emptyModules) > 0
            ? "You have " . count($emptyModules) . " empty modules: " . implode(', ', $emptyModules) . ". Want help populating them?"
            : "Your CRM is well populated across all modules.";

        return [
            'success' => true,
            'message' => $summary,
            'stats' => $stats,
            'custom_workflows' => $customWorkflows,
        ];
    }
}
