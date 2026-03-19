<?php

require_once __DIR__ . '/Base.php';

/**
 * UI Action: opens a specific record's detail or edit view.
 */
class AIAction_OpenRecord extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'open_record',
            'description' => 'Open a specific CRM record in detail view or edit mode. Use when user says "open lead #123" or "edit contact John Smith".',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Module name',
                    ],
                    'record_id' => [
                        'type' => 'integer',
                        'description' => 'Record ID to open',
                    ],
                    'mode' => [
                        'type' => 'string',
                        'enum' => ['detail', 'edit'],
                        'description' => 'View mode: detail (read-only) or edit',
                    ],
                ],
                'required' => ['module', 'record_id'],
            ],
        ];
    }

    public function execute(array $params): array {
        $module = $params['module'];
        $recordId = (int)$params['record_id'];
        $mode = $params['mode'] ?? 'detail';

        // Validate record exists
        $record = $this->getRecord($recordId);
        if (!$record) {
            return ['success' => false, 'message' => "Record #$recordId not found."];
        }

        $view = ($mode === 'edit') ? 'Edit' : 'Detail';
        $url = "index.php?module=$module&view=$view&record=$recordId";

        $label = $record->getName();

        return [
            'success' => true,
            'message' => "Opening $label",
            'ui_action' => [
                'type' => 'navigate',
                'url' => $url,
            ],
        ];
    }
}
