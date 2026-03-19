<?php

require_once __DIR__ . '/Base.php';

/**
 * UI Action: navigates the user's browser to a filtered list view.
 * Returns a ui_action that the chat widget JS executes.
 */
class AIAction_NavigateToView extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'navigate_to_view',
            'description' => 'Navigate the user to a module list view with optional filters. Use this when the user wants to SEE records (e.g., "show me leads to call today", "open my deals closing this month").',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Module name (Leads, Contacts, Accounts, Potentials, HelpDesk, etc.)',
                    ],
                    'search_key' => [
                        'type' => 'string',
                        'description' => 'Field name to filter by (e.g., date_call, leadsource, leadstatus, assigned_user_id)',
                    ],
                    'search_value' => [
                        'type' => 'string',
                        'description' => 'Value to filter for',
                    ],
                    'operator' => [
                        'type' => 'string',
                        'enum' => ['e', 's', 'c', 'k', 'l', 'g', 'b', 'a'],
                        'description' => 'Filter operator: e=equals, s=starts with, c=contains, k=not contains, l=less than, g=greater than, b=before (date), a=after (date)',
                    ],
                    'viewname' => [
                        'type' => 'string',
                        'description' => 'Custom view ID to use (optional, omit for default "All" view)',
                    ],
                ],
                'required' => ['module'],
            ],
        ];
    }

    public function execute(array $params): array {
        $module = $params['module'];
        $searchKey = $params['search_key'] ?? '';
        $searchValue = $params['search_value'] ?? '';
        $operator = $params['operator'] ?? 'e';
        $viewname = $params['viewname'] ?? '';

        // Validate module exists
        $moduleModel = $this->getModule($module);
        if (!$moduleModel) {
            return ['success' => false, 'message' => "Module '$module' not found."];
        }

        // Build URL
        $url = "index.php?module=$module&view=List";

        if ($viewname) {
            $url .= "&viewname=$viewname";
        }

        if ($searchKey && $searchValue !== '') {
            $url .= "&search_key=" . urlencode($searchKey);
            $url .= "&search_value=" . urlencode($searchValue);
            $url .= "&operator=" . urlencode($operator);
        }

        $description = "Opening $module";
        if ($searchKey) {
            $description .= " filtered by $searchKey";
        }

        return [
            'success' => true,
            'message' => $description,
            'ui_action' => [
                'type' => 'navigate',
                'url' => $url,
            ],
        ];
    }
}
