<?php

use Laravel\Mcp\Facades\Mcp;

// Register WhatsApp MCP Server for internal use
// First arg is a unique handle, second is the server class.
Mcp::local('whatsapp-mcp', \App\Mcp\Servers\WhatsAppServer::class);

// Optionally expose over HTTP for external AI providers
// WARNING: Add proper authentication middleware before uncommenting
// Mcp::web('/mcp/whatsapp-mcp', \App\Mcp\Servers\WhatsAppServer::class);
