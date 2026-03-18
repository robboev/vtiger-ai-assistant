module.exports = {
    // Anthropic API key
    anthropicApiKey: 'sk-ant-xxx',

    // Claude model for code generation
    model: 'claude-sonnet-4-6',

    // MySQL connection for the agent queue
    db: {
        host: 'localhost',
        user: 'vtiger84',
        password: '',
        database: 'vtiger_yad2_dev',
    },

    // Path to vtiger installation (for reading code)
    vtigerRoot: '/var/www/html/vtiger84_clean',

    // Path to the AI Assistant module actions directory
    actionsDir: '/var/www/html/vtiger84_clean/modules/AIAssistant/actions',

    // Auto-approve actions that pass all validation (false = require admin approval)
    autoApprove: false,

    // Max files the agent can read per request
    maxFileReads: 20,

    // Polling interval in seconds
    pollInterval: 30,
};
