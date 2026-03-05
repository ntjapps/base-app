<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure AI provider settings and credentials. Supports multiple
    | providers with runtime override per-conversation capability.
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    'providers' => [

        'gemini' => [
            'enabled' => (bool) env('GEMINIAI_ENABLED', false),
            'api_key' => env('GEMINIAI_API_KEY', 'your_default_api_key'),
            'base_url' => env('GEMINIAI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
            'file_upload_base_url' => env('GEMINIAI_FILE_UPLOAD_BASE_URL', 'https://generativelanguage.googleapis.com/upload/v1beta/files'),
            'selected_model' => env('GEMINIAI_MODELS', 'gemini-2.5-flash-lite'),
            'timeout' => env('GEMINIAI_TIMEOUT', 30),
        ],

        'openai' => [
            'enabled' => (bool) env('OPENAI_ENABLED', false),
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1/'),
            'selected_model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
            'timeout' => env('OPENAI_TIMEOUT', 30),
        ],

        'anthropic' => [
            'enabled' => (bool) env('ANTHROPIC_ENABLED', false),
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/'),
            'selected_model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
            'timeout' => env('ANTHROPIC_TIMEOUT', 30),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | MCP (Model Context Protocol) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Laravel MCP servers, tools, and resources.
    |
    */

    'mcp' => [
        'enabled' => env('MCP_ENABLED', true),
        'default_server' => 'whatsapp-mcp',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Tools Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting and security for AI tool invocations.
    |
    */

    'tools' => [
        'rate_limit' => [
            'max_attempts' => env('AI_TOOLS_RATE_LIMIT_MAX', 10),
            'decay_minutes' => env('AI_TOOLS_RATE_LIMIT_DECAY', 1),
        ],
        'logging' => [
            'enabled' => env('AI_TOOLS_LOGGING', true),
            'channel' => env('AI_TOOLS_LOG_CHANNEL', 'stack'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Model Instructions
    |--------------------------------------------------------------------------
    |
    | Default instruction keys. (Note: runtime file fallback removed)
    |
    */

    'instructions' => [
        'default_key' => env('AI_DEFAULT_INSTRUCTION_KEY', 'whatsapp_default'),
        'cache_duration' => env('AI_INSTRUCTION_CACHE_DURATION', 3600),
    ],

];
