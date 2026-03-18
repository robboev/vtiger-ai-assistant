<?php
/**
 * AI Assistant configuration.
 * Copy this file to config_ai.php and fill in your values.
 */
return [
    // Anthropic API key (required)
    'anthropic_api_key' => 'sk-ant-xxx',

    // Claude model to use
    'model' => 'claude-sonnet-4-6',

    // Rate limit per user per hour
    'rate_limit' => 100,

    // Enable/disable the chat widget globally
    'enabled' => true,
];
