/**
 * CLI Agent Builder
 *
 * Reads a queued request, analyzes vtiger code to understand how to
 * implement the requested capability, generates a new action class,
 * validates it, and registers it.
 *
 * Usage:
 *   node builder.js --queue-id=123
 *   node builder.js --action="send_whatsapp" --description="Send WhatsApp message when deal closes"
 */

const fs = require('fs');
const path = require('path');
const Anthropic = require('@anthropic-ai/sdk');
const { getDb, closeDb } = require('./db');

// Load config
const configPath = path.join(__dirname, 'config.js');
if (!fs.existsSync(configPath)) {
    console.error('Missing config.js — copy config.example.js and fill in values');
    process.exit(1);
}
const config = require('./config');

const client = new Anthropic({ apiKey: config.anthropicApiKey });

// Forbidden patterns that generated code must not contain
const FORBIDDEN_PATTERNS = [
    'exec(', 'system(', 'shell_exec(', 'passthru(', 'popen(', 'proc_open(',
    'eval(', 'assert(',
    'file_put_contents(', 'fwrite(', 'fputs(',
    'unlink(', 'rmdir(',
    'DROP ', 'TRUNCATE ', 'ALTER TABLE', 'GRANT ', 'REVOKE ',
    '$_GET', '$_POST', '$_REQUEST', '$_FILES', '$_SERVER', '$_COOKIE',
    'config.inc.php',
    'include_once(', 'require_once(',
    'call_user_func(', 'call_user_func_array(',
    'unserialize(',
];

/**
 * Read a file from the vtiger installation.
 */
function readVtigerFile(relativePath) {
    const fullPath = path.join(config.vtigerRoot, relativePath);
    if (!fs.existsSync(fullPath)) return null;

    // Security: prevent path traversal
    const resolved = path.resolve(fullPath);
    if (!resolved.startsWith(path.resolve(config.vtigerRoot))) {
        console.error(`Path traversal blocked: ${relativePath}`);
        return null;
    }

    return fs.readFileSync(fullPath, 'utf-8');
}

/**
 * Find relevant vtiger files for a given capability.
 */
function findRelevantFiles(actionDescription) {
    const searchDirs = [
        'modules',
        'include',
        'vtlib/Vtiger',
    ];

    const results = [];

    function walkDir(dir, maxDepth = 3, depth = 0) {
        if (depth > maxDepth || results.length > 50) return;
        const fullDir = path.join(config.vtigerRoot, dir);
        if (!fs.existsSync(fullDir)) return;

        try {
            const entries = fs.readdirSync(fullDir, { withFileTypes: true });
            for (const entry of entries) {
                if (entry.name.startsWith('.')) continue;
                const relPath = path.join(dir, entry.name);

                if (entry.isDirectory()) {
                    walkDir(relPath, maxDepth, depth + 1);
                } else if (entry.name.endsWith('.php')) {
                    results.push(relPath);
                }
            }
        } catch (e) {
            // permission error, skip
        }
    }

    for (const dir of searchDirs) {
        walkDir(dir);
    }

    return results;
}

/**
 * Read the Base.php action template for reference.
 */
function getBaseTemplate() {
    const basePath = path.join(__dirname, '..', 'module-src', 'modules', 'AIAssistant', 'actions', 'Base.php');
    return fs.readFileSync(basePath, 'utf-8');
}

/**
 * Read an example action for reference.
 */
function getExampleAction() {
    const exPath = path.join(__dirname, '..', 'module-src', 'modules', 'AIAssistant', 'actions', 'CreateWorkflow.php');
    return fs.readFileSync(exPath, 'utf-8');
}

/**
 * Validate generated PHP code against forbidden patterns.
 */
function validateCode(code) {
    const violations = [];

    for (const pattern of FORBIDDEN_PATTERNS) {
        if (code.toLowerCase().includes(pattern.toLowerCase())) {
            violations.push(`Forbidden pattern: ${pattern}`);
        }
    }

    if (!code.includes('extends AIAction_Base')) {
        violations.push('Must extend AIAction_Base');
    }

    if (!code.includes('function definition()')) {
        violations.push('Must implement definition()');
    }

    if (!code.includes('function execute(')) {
        violations.push('Must implement execute()');
    }

    return violations;
}

/**
 * Use Claude to generate a new action class.
 */
async function generateAction(actionName, description, relevantCode) {
    const baseTemplate = getBaseTemplate();
    const exampleAction = getExampleAction();

    const systemPrompt = `You are a PHP code generator for a vtiger CRM AI Assistant module.
Your job is to generate a new action class that extends AIAction_Base.

RULES:
- You MUST extend AIAction_Base
- You MUST implement static definition() returning tool schema
- You MUST implement execute(array $params) returning ['success' => bool, 'message' => string]
- You MUST use $this->db (PearDatabase) for queries
- You MUST use $this->getRecord() and $this->getModule() helpers
- You MUST use parameterized queries ($this->db->pquery with ?)
- You MUST override declaresWrite() to return true if the action modifies data
- Class name MUST be AIAction_{PascalCase} (e.g., AIAction_SendWhatsApp)
- File MUST start with <?php and require_once __DIR__ . '/Base.php';

FORBIDDEN (code will be rejected if these are found):
- exec(), system(), shell_exec(), eval(), assert()
- file_put_contents(), fwrite(), unlink(), rmdir()
- DROP, TRUNCATE, ALTER TABLE, GRANT, REVOKE
- $_GET, $_POST, $_REQUEST, $_FILES, $_SERVER, $_COOKIE
- include_once(), require_once() (except for Base.php)
- call_user_func(), unserialize()

Output ONLY the PHP file content. No markdown, no explanation.`;

    const userPrompt = `Generate an action class for: "${description}"
Action name: ${actionName}

## Base class for reference:
\`\`\`php
${baseTemplate}
\`\`\`

## Example action (CreateWorkflow) for reference:
\`\`\`php
${exampleAction}
\`\`\`

## Relevant vtiger code for context:
${relevantCode}

Generate the complete PHP action class file.`;

    const response = await client.messages.create({
        model: config.model,
        max_tokens: 4096,
        system: systemPrompt,
        messages: [{ role: 'user', content: userPrompt }],
    });

    let code = response.content[0].text;

    // Clean up — remove markdown fences if present
    code = code.replace(/^```php\n?/, '').replace(/\n?```$/, '').trim();

    return code;
}

/**
 * Use Claude to identify which vtiger files are relevant for a given action.
 */
async function identifyRelevantFiles(actionName, description, allFiles) {
    // Take a sample of file paths for Claude to pick from
    const fileSample = allFiles.slice(0, 200).join('\n');

    const response = await client.messages.create({
        model: config.model,
        max_tokens: 1024,
        system: 'You help identify relevant vtiger CRM source files. Return ONLY file paths, one per line. Max 10 files.',
        messages: [{
            role: 'user',
            content: `I need to build an action that: "${description}" (action name: ${actionName})

Which of these vtiger files would contain relevant code to understand how to implement this?

${fileSample}

Return only the most relevant file paths (max 10), one per line.`,
        }],
    });

    const paths = response.content[0].text
        .split('\n')
        .map(l => l.trim())
        .filter(l => l && !l.startsWith('#') && l.endsWith('.php'));

    return paths.slice(0, config.maxFileReads);
}

/**
 * Main build flow for a single action request.
 */
async function buildAction(actionName, description, queueId = null) {
    const db = await getDb(config.db);

    try {
        // Mark as processing
        if (queueId) {
            await db.execute(
                "UPDATE vtiger_ai_agent_queue SET status='processing', started_at=NOW() WHERE id=?",
                [queueId]
            );
        }

        console.log(`\n--- Building action: ${actionName} ---`);
        console.log(`Description: ${description}`);

        // Step 1: Find relevant vtiger files
        console.log('\n[1/5] Scanning vtiger codebase...');
        const allFiles = findRelevantFiles(description);
        console.log(`Found ${allFiles.length} PHP files to consider.`);

        // Step 2: Ask Claude which files are relevant
        console.log('\n[2/5] Identifying relevant files...');
        const relevantPaths = await identifyRelevantFiles(actionName, description, allFiles);
        console.log(`Selected ${relevantPaths.length} files to read.`);

        // Step 3: Read relevant files
        console.log('\n[3/5] Reading vtiger source code...');
        let relevantCode = '';
        for (const filePath of relevantPaths) {
            const content = readVtigerFile(filePath);
            if (content) {
                // Truncate large files
                const truncated = content.length > 3000 ? content.substring(0, 3000) + '\n// ... truncated' : content;
                relevantCode += `\n### ${filePath}\n\`\`\`php\n${truncated}\n\`\`\`\n`;
                console.log(`  Read: ${filePath} (${content.length} bytes)`);
            }
        }

        // Step 4: Generate the action
        console.log('\n[4/5] Generating action class...');
        const code = await generateAction(actionName, description, relevantCode);

        // Step 5: Validate
        console.log('\n[5/5] Validating generated code...');
        const violations = validateCode(code);

        if (violations.length > 0) {
            console.error('VALIDATION FAILED:');
            violations.forEach(v => console.error(`  - ${v}`));

            if (queueId) {
                await db.execute(
                    "UPDATE vtiger_ai_agent_queue SET status='failed', error_message=? WHERE id=?",
                    [violations.join('; '), queueId]
                );
            }
            return { success: false, errors: violations };
        }

        // Convert action name to PascalCase for filename
        const className = actionName
            .split('_')
            .map(w => w.charAt(0).toUpperCase() + w.slice(1))
            .join('');

        const outputPath = path.join(config.actionsDir, `${className}.php`);

        // Write the file
        fs.writeFileSync(outputPath, code, 'utf-8');
        console.log(`\nAction written to: ${outputPath}`);

        // Register in DB
        const status = config.autoApprove ? 'active' : 'pending_review';
        await db.execute(
            `INSERT INTO vtiger_ai_action_registry (action_name, source, status, generated_by)
             VALUES (?, 'generated', ?, 'cli-agent')
             ON DUPLICATE KEY UPDATE status=?, generated_by='cli-agent'`,
            [actionName, status, status]
        );

        // Update queue
        if (queueId) {
            await db.execute(
                "UPDATE vtiger_ai_agent_queue SET status='completed', result_action=?, completed_at=NOW() WHERE id=?",
                [actionName, queueId]
            );
        }

        console.log(`\nAction registered as: ${status}`);
        console.log('--- Build complete ---\n');

        return { success: true, actionName, className, status, outputPath };

    } catch (error) {
        console.error('Build failed:', error.message);

        if (queueId) {
            await db.execute(
                "UPDATE vtiger_ai_agent_queue SET status='failed', error_message=? WHERE id=?",
                [error.message, queueId]
            );
        }

        return { success: false, errors: [error.message] };
    } finally {
        await closeDb(db);
    }
}

// CLI entry point
async function main() {
    const args = {};
    process.argv.slice(2).forEach(arg => {
        const [key, val] = arg.replace(/^--/, '').split('=');
        args[key] = val;
    });

    if (args['queue-id']) {
        // Build from queue
        const db = await getDb(config.db);
        const [rows] = await db.execute(
            "SELECT * FROM vtiger_ai_agent_queue WHERE id = ? AND status = 'pending'",
            [args['queue-id']]
        );
        await closeDb(db);

        if (rows.length === 0) {
            console.error(`Queue item #${args['queue-id']} not found or not pending.`);
            process.exit(1);
        }

        const item = rows[0];
        await buildAction(item.requested_action, `User requested: ${item.requested_action}`, item.id);

    } else if (args.action && args.description) {
        // Build from CLI args
        await buildAction(args.action, args.description);

    } else {
        console.log('Usage:');
        console.log('  node builder.js --queue-id=123');
        console.log('  node builder.js --action=send_whatsapp --description="Send WhatsApp when deal closes"');
        process.exit(1);
    }
}

module.exports = { buildAction, validateCode };

if (require.main === module) {
    main().catch(err => {
        console.error(err);
        process.exit(1);
    });
}
