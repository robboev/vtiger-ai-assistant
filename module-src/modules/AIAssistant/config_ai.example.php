<?php
/**
 * AI Assistant configuration.
 * Copy this file to config_ai.php and fill in your values.
 *
 * Supported providers: anthropic, openai, ollama
 */
return [
    // Provider: 'anthropic', 'openai', or 'ollama'
    'provider' => 'anthropic',

    // API key (not needed for ollama)
    'api_key' => '',

    // Model name per provider:
    //   anthropic: claude-haiku-4-5-20251001 (cheapest), claude-sonnet-4-6, claude-opus-4-6
    //   openai:    gpt-4o-mini (cheapest), gpt-4o, gpt-4-turbo
    //   ollama:    llama3, mistral, mixtral, etc. (free, runs locally)
    'model' => 'claude-haiku-4-5-20251001',

    // API base URL (only change for ollama or custom endpoints)
    //   anthropic: https://api.anthropic.com
    //   openai:    https://api.openai.com
    //   ollama:    http://localhost:11434
    'api_base_url' => '',

    // Rate limit per user per hour
    'rate_limit' => 100,

    // Enable/disable the chat widget globally
    'enabled' => true,

    // Max tokens for response
    'max_tokens' => 1024,
];
