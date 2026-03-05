<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Prompts\SupportAgentPrompt;
use App\Mcp\Resources\ConversationHistoryResource;
use App\Mcp\Tools\GetConversationHistoryTool;
use App\Mcp\Tools\GetProductsTool;
use App\Mcp\Tools\GetUserByPhoneTool;
use Laravel\Mcp\Server;

class WhatsAppServer extends Server
{
    /**
     * MCP Tools available to this server.
     *
     * @var array<int, class-string>
     */
    public array $tools = [
        GetUserByPhoneTool::class,
        GetConversationHistoryTool::class,
        GetProductsTool::class,
    ];

    /**
     * MCP Prompts available to this server.
     *
     * @var array<int, class-string>
     */
    public array $prompts = [
        SupportAgentPrompt::class,
    ];

    /**
     * MCP Resources available to this server.
     *
     * Note: Resources are dynamically instantiated with parameters.
     * Register specific resource instances in the registration method if needed.
     *
     * @var array<int, class-string>
     */
    public array $resources = [
        // ConversationHistoryResource requires constructor params,
        // so we'll register it dynamically when needed
    ];

    /**
     * Server name.
     */
    public function name(): string
    {
        return 'WhatsApp Support Server';
    }

    /**
     * Server description.
     */
    public function description(): string
    {
        return 'MCP server for WhatsApp Business Platform integration with AI-powered support tools, prompts, and conversation context.';
    }
}
