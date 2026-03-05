<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class GetUserByPhoneTool extends Tool
{
    protected string $name = 'get-user-by-phone';

    protected string $description = 'Fetch minimal user details by phone number. Returns user ID, name, email, and division if found.';

    public function handle(Request $request): ResponseFactory
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $request->get('phone');

        // Log tool invocation for audit
        Log::channel(config('ai.tools.logging.channel', 'stack'))
            ->info('AI Tool Invoked: GetUserByPhone', [
                'tool' => $this->name,
                'phone' => $phone,
                'timestamp' => now(),
            ]);

        // Find user by phone
        // Note: This searches username field as phone numbers may be used as usernames
        // Adjust the query based on your actual user schema if phone is in a different field
        $user = User::where('username', $phone)->first();

        if (! $user) {
            return Response::make(Response::json([
                'found' => false,
                'id' => null,
                'name' => null,
                'email' => null,
                'division' => null,
            ]));
        }

        // Return sanitized minimal user data
        return Response::make(Response::json([
            'found' => true,
            'id' => $user->id,
            'name' => $user->name,
            'email' => null, // Don't expose email to AI for privacy
            'division' => $user->division,
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'phone' => $schema->string()
                ->description('The user phone number to search for')
                ->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'found' => $schema->boolean()
                ->description('Whether a user was found'),
            'id' => $schema->string()
                ->description('User UUID')
                ->nullable(),
            'name' => $schema->string()
                ->description('User display name')
                ->nullable(),
            'email' => $schema->string()
                ->description('User email (sanitized)')
                ->nullable(),
            'division' => $schema->string()
                ->description('User division (support, billing, etc.)')
                ->nullable(),
        ];
    }
}
