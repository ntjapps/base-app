<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class GetProductsTool extends Tool
{
    protected string $name = 'get-products';

    protected string $description = 'Retrieve product listing or summary. This is a placeholder/stub tool that should be implemented based on actual product schema.';

    public function handle(Request $request): ResponseFactory
    {
        $request->validate([
            'user_id' => 'sometimes|string',
            'category' => 'sometimes|string',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $userId = $request->get('user_id');
        $category = $request->get('category');
        $limit = $request->get('limit', 10);

        // Log tool invocation for audit
        Log::channel(config('ai.tools.logging.channel', 'stack'))
            ->info('AI Tool Invoked: GetProducts', [
                'tool' => $this->name,
                'user_id' => $userId,
                'category' => $category,
                'limit' => $limit,
                'timestamp' => now(),
            ]);

        // TODO: Replace with actual product query logic
        // This is a placeholder implementation
        // Example:
        // $products = Product::when($userId, fn($q) => $q->forUser($userId))
        //     ->when($category, fn($q) => $q->category($category))
        //     ->limit($limit)
        //     ->get();

        return Response::make(Response::json([
            'products' => [],
            'total' => 0,
            'message' => 'Product integration not yet implemented. Please implement based on your product schema.',
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->string()
                ->description('Optional user ID to filter products for a specific user'),
            'category' => $schema->string()
                ->description('Optional category filter'),
            'limit' => $schema->integer()
                ->description('Maximum number of products to return (1-50, default: 10)')
                ->min(1)
                ->max(50),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'products' => $schema->array()
                ->description('Array of product objects'),
            'total' => $schema->integer()
                ->description('Total number of products returned'),
            'message' => $schema->string()
                ->description('Status or error message'),
        ];
    }
}
