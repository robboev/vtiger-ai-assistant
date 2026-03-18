<?php

/**
 * Base class for all AI Assistant actions.
 * Every action (built-in or CLI-generated) must extend this class.
 */
abstract class AIAction_Base {

    /** @var PearDatabase */
    protected $db;

    /** @var Users */
    protected $currentUser;

    /** Forbidden patterns - code containing these is rejected at registration */
    private static $FORBIDDEN_PATTERNS = [
        // Shell execution
        'exec(', 'system(', 'shell_exec(', 'passthru(', 'popen(', 'proc_open(',
        'pcntl_exec(',
        // Code evaluation
        'eval(', 'assert(', 'preg_replace_callback_array(',
        // File system writes
        'file_put_contents(', 'fwrite(', 'fputs(',
        // File system deletes
        'unlink(', 'rmdir(', 'rename(',
        // Destructive SQL
        'DROP ', 'TRUNCATE ', 'ALTER TABLE', 'GRANT ', 'REVOKE ',
        // Direct superglobals (must go through validated params)
        '$_GET', '$_POST', '$_REQUEST', '$_FILES', '$_SERVER', '$_COOKIE',
        // Config access
        'config.inc.php', 'config.php',
        // Include/require arbitrary files
        'include_once(', 'require_once(',
        // Reflection/dynamic calls
        'call_user_func(', 'call_user_func_array(',
        // Serialization attacks
        'unserialize(',
    ];

    /** Allowed vtiger classes that actions can use */
    private static $ALLOWED_CLASSES = [
        'Vtiger_Record_Model',
        'Vtiger_Module_Model',
        'Vtiger_Field_Model',
        'Vtiger_ListView_Model',
        'Vtiger_RelationListView_Model',
        'VTWorkflowManager',
        'VTTaskManager',
        'PearDatabase',
        'Vtiger_Util_Helper',
        'Vtiger_Functions',
    ];

    public function __construct() {
        $this->db = PearDatabase::getInstance();
        global $current_user;
        $this->currentUser = $current_user;
    }

    /**
     * Return the tool definition for Claude API tool_use.
     * Must return: name, description, parameters (JSON Schema)
     */
    abstract public static function definition(): array;

    /**
     * Execute the action with validated parameters.
     * Must return: ['success' => bool, 'message' => string, ...]
     */
    abstract public function execute(array $params): array;

    /**
     * Whether this action writes data (vs read-only).
     * Override to return true if action modifies DB.
     */
    public function declaresWrite(): bool {
        return false;
    }

    /**
     * Maximum execution time in seconds.
     */
    public function maxExecutionTime(): int {
        return 10;
    }

    /**
     * Maximum records this action can affect in one call.
     */
    public function maxRecords(): int {
        return 50;
    }

    /**
     * Validate parameters against the tool definition schema.
     */
    public function validateParams(array $params): array {
        $errors = [];
        $def = static::definition();
        $properties = $def['parameters']['properties'] ?? [];
        $required = $def['parameters']['required'] ?? [];

        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                $errors[] = "Missing required parameter: $field";
            }
        }

        foreach ($params as $key => $value) {
            if (!isset($properties[$key])) {
                $errors[] = "Unknown parameter: $key";
            }
        }

        return $errors;
    }

    /**
     * Static validation of generated action source code.
     * Returns array of violations found, empty = safe.
     */
    public static function validateSourceCode(string $filePath): array {
        $violations = [];

        if (!file_exists($filePath)) {
            return ['File not found: ' . $filePath];
        }

        $code = file_get_contents($filePath);

        // Check forbidden patterns
        foreach (self::$FORBIDDEN_PATTERNS as $pattern) {
            if (stripos($code, $pattern) !== false) {
                $violations[] = "Forbidden pattern found: $pattern";
            }
        }

        // Must extend AIAction_Base
        if (strpos($code, 'extends AIAction_Base') === false) {
            $violations[] = "Action must extend AIAction_Base";
        }

        // Must implement definition() and execute()
        if (strpos($code, 'function definition()') === false) {
            $violations[] = "Action must implement definition()";
        }
        if (strpos($code, 'function execute(') === false) {
            $violations[] = "Action must implement execute()";
        }

        return $violations;
    }

    /**
     * Get the list of allowed vtiger classes for reference.
     */
    public static function getAllowedClasses(): array {
        return self::$ALLOWED_CLASSES;
    }

    /**
     * Helper: get a record model safely.
     */
    protected function getRecord(int $recordId): ?Vtiger_Record_Model {
        try {
            return Vtiger_Record_Model::getInstanceById($recordId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Helper: get module model safely.
     */
    protected function getModule(string $moduleName): ?Vtiger_Module_Model {
        try {
            return Vtiger_Module_Model::getInstance($moduleName);
        } catch (Exception $e) {
            return null;
        }
    }
}
