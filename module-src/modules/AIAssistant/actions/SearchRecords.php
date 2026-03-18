<?php

require_once __DIR__ . '/Base.php';

/**
 * Search records across modules.
 */
class AIAction_SearchRecords extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'search_records',
            'description' => 'Search for records in a module by keyword or field value.',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Module to search in (e.g., Leads, Contacts)',
                    ],
                    'query' => [
                        'type' => 'string',
                        'description' => 'Search keyword (searches across name, email, phone fields)',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Max results to return (default 10, max 50)',
                    ],
                ],
                'required' => ['module', 'query'],
            ],
        ];
    }

    public function execute(array $params): array {
        $moduleName = $params['module'];
        $query = $params['query'];
        $limit = min($params['limit'] ?? 10, 50);

        $module = $this->getModule($moduleName);
        if (!$module) {
            return ['success' => false, 'message' => "Module '$moduleName' not found."];
        }

        // Use vtiger's list view model for search
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('limit', $limit);

        $listViewModel = Vtiger_ListView_Model::getInstance($moduleName);
        $listViewModel->set('search_key', 'lastname');
        $listViewModel->set('search_value', $query);
        $listViewModel->set('operator', 'c'); // contains

        try {
            $entries = $listViewModel->getListViewEntries($pagingModel);
            $records = [];

            foreach ($entries as $record) {
                $data = $record->getData();
                // Return only common fields
                $records[] = [
                    'id' => $record->getId(),
                    'label' => $record->getName(),
                    'assigned_to' => $data['assigned_user_id'] ?? '',
                    'created_time' => $data['createdtime'] ?? '',
                ];
            }

            $count = count($records);
            $message = $count === 0
                ? "No records found in $moduleName matching '$query'."
                : "Found $count record(s) in $moduleName matching '$query'.";

            return [
                'success' => true,
                'message' => $message,
                'records' => $records,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Search failed: " . $e->getMessage(),
            ];
        }
    }
}
