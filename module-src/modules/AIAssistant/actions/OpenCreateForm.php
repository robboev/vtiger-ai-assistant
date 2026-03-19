<?php

require_once __DIR__ . '/Base.php';

/**
 * UI Action: opens the record creation form with pre-filled fields.
 */
class AIAction_OpenCreateForm extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'open_create_form',
            'description' => 'Open the record creation form for a module, optionally with pre-filled field values. Use when user says "create a new lead" or "add a contact named John".',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Module name (Leads, Contacts, Accounts, etc.)',
                    ],
                    'prefill' => [
                        'type' => 'object',
                        'description' => 'Field values to pre-fill (e.g., {"lastname": "Smith", "company": "Acme"})',
                    ],
                ],
                'required' => ['module'],
            ],
        ];
    }

    public function execute(array $params): array {
        $module = $params['module'];
        $prefill = $params['prefill'] ?? [];

        $moduleModel = $this->getModule($module);
        if (!$moduleModel) {
            return ['success' => false, 'message' => "Module '$module' not found."];
        }

        $url = "index.php?module=$module&view=Edit";

        foreach ($prefill as $field => $value) {
            $url .= '&' . urlencode($field) . '=' . urlencode($value);
        }

        $description = "Opening new $module form";
        if (!empty($prefill)) {
            $description .= " with pre-filled data";
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
