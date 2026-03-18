<?php

require_once __DIR__ . '/Base.php';

/**
 * Creates a single record in any module.
 */
class AIAction_CreateRecord extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'create_record',
            'description' => 'Create a new record in a CRM module (e.g., create a lead, contact, account).',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Module name (e.g., Leads, Contacts, Accounts)',
                    ],
                    'fields' => [
                        'type' => 'object',
                        'description' => 'Field name-value pairs for the record (e.g., {"lastname": "Smith", "email": "smith@example.com"})',
                    ],
                ],
                'required' => ['module', 'fields'],
            ],
        ];
    }

    public function declaresWrite(): bool {
        return true;
    }

    public function execute(array $params): array {
        $moduleName = $params['module'];
        $fields = $params['fields'];

        $module = $this->getModule($moduleName);
        if (!$module) {
            return ['success' => false, 'message' => "Module '$moduleName' not found."];
        }

        // Create record using vtiger model
        $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);

        // Set assigned user to current user
        $recordModel->set('assigned_user_id', $this->currentUser->id);

        // Set provided fields
        foreach ($fields as $fieldName => $value) {
            $recordModel->set($fieldName, $value);
        }

        try {
            $recordModel->save();
            $recordId = $recordModel->getId();

            return [
                'success' => true,
                'message' => "Created $moduleName record #$recordId.",
                'record_id' => $recordId,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Failed to create record: " . $e->getMessage(),
            ];
        }
    }
}
