<?php

require_once __DIR__ . '/Base.php';

/**
 * Creates a vtiger workflow with optional email/update tasks.
 */
class AIAction_CreateWorkflow extends AIAction_Base {

    public static function definition(): array {
        return [
            'name' => 'create_workflow',
            'description' => 'Create a workflow automation for a module. Can trigger on record create, update, or both.',
            'parameters' => [
                'properties' => [
                    'module' => [
                        'type' => 'string',
                        'description' => 'Module name (e.g., Leads, Contacts, Potentials)',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Short description of what this workflow does',
                    ],
                    'trigger' => [
                        'type' => 'string',
                        'enum' => ['on_create', 'on_modify', 'on_every_save', 'on_schedule'],
                        'description' => 'When the workflow should trigger',
                    ],
                    'task_type' => [
                        'type' => 'string',
                        'enum' => ['email', 'update_field', 'create_todo'],
                        'description' => 'Type of task to add to the workflow',
                    ],
                    'task_config' => [
                        'type' => 'object',
                        'description' => 'Configuration for the task (varies by task_type)',
                    ],
                ],
                'required' => ['module', 'description', 'trigger', 'task_type'],
            ],
        ];
    }

    public function declaresWrite(): bool {
        return true;
    }

    public function execute(array $params): array {
        $module = $params['module'];
        $description = $params['description'];
        $trigger = $params['trigger'];
        $taskType = $params['task_type'];
        $taskConfig = $params['task_config'] ?? [];

        // Validate module exists
        $moduleModel = $this->getModule($module);
        if (!$moduleModel) {
            return ['success' => false, 'message' => "Module '$module' not found."];
        }

        // Map trigger to vtiger execution condition
        $triggerMap = [
            'on_create' => 1,  // ON_FIRST_SAVE
            'on_modify' => 3,  // ON_MODIFY
            'on_every_save' => 2, // ONCE
            'on_schedule' => 6, // ON_SCHEDULE
        ];

        $executionCondition = $triggerMap[$trigger] ?? 2;

        // Create workflow
        $wfManager = new VTWorkflowManager($this->db);
        $workflow = $wfManager->newWorkflow($module);
        $workflow->description = $description;
        $workflow->executionCondition = $executionCondition;
        $workflow->defaultworkflow = 0;
        $wfManager->save($workflow);

        $workflowId = $workflow->id;

        // Create task based on type
        $taskManager = new VTTaskManager($this->db);

        switch ($taskType) {
            case 'email':
                $task = $taskManager->createTask('VTEmailTask', $workflowId);
                $task->active = true;
                $task->summary = $taskConfig['subject'] ?? $description;
                $task->recepient = $taskConfig['recipient'] ?? '$current_user_email';
                $task->subject = $taskConfig['subject'] ?? "Notification: $description";
                $task->content = $taskConfig['body'] ?? "This is an automated notification for $module.";
                $taskManager->saveTask($task);
                break;

            case 'update_field':
                $task = $taskManager->createTask('VTUpdateFieldsTask', $workflowId);
                $task->active = true;
                $task->summary = $description;
                $task->field_value_mapping = json_encode([
                    [
                        'fieldname' => $taskConfig['field'] ?? '',
                        'valuetype' => 'rawtext',
                        'value' => $taskConfig['value'] ?? '',
                    ],
                ]);
                $taskManager->saveTask($task);
                break;

            case 'create_todo':
                $task = $taskManager->createTask('VTCreateTodoTask', $workflowId);
                $task->active = true;
                $task->summary = $taskConfig['title'] ?? "Follow up: $description";
                $task->todo = $taskConfig['title'] ?? "Follow up on $module record";
                $task->description = $taskConfig['description'] ?? '';
                $task->days_start = $taskConfig['days_from_now'] ?? 1;
                $task->days_end = ($taskConfig['days_from_now'] ?? 1) + 1;
                $task->status = 'Not Started';
                $task->priority = 'High';
                $taskManager->saveTask($task);
                break;
        }

        return [
            'success' => true,
            'message' => "Workflow created: '$description' for $module (triggers $trigger). Task type: $taskType.",
            'workflow_id' => $workflowId,
        ];
    }
}
