<?php

use App\Mcp\Prompts\SupportAgentPrompt;
use App\Mcp\Resources\ConversationHistoryResource;
use App\Mcp\Servers\WhatsAppServer;
use App\Mcp\Tools\GetConversationHistoryTool;
use App\Mcp\Tools\GetProductsTool;
use App\Mcp\Tools\GetUserByPhoneTool;
use App\Models\AiModelInstruction;
use App\Models\User;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Contracts\Transport;

describe('WhatsApp MCP', function () {
    it('exposes server metadata', function () {
        $s = new WhatsAppServer(Mockery::mock(Transport::class));
        expect($s->name())->toBeString();
        expect($s->description())->toBeString();
        expect($s->tools)->toBeArray();
        expect($s->prompts)->toBeArray();
    });

    it('handles get-user-by-phone tool', function () {
        $phone = '628123456789';
        User::factory()->create(['username' => $phone, 'name' => 'Test User']);

        $req = Mockery::mock(Request::class);
        $req->shouldReceive('validate')->once();
        $req->shouldReceive('get')->with('phone')->andReturn($phone);

        $tool = new GetUserByPhoneTool;
        $factory = $tool->handle($req);
        $json = (string) $factory->responses()->first()->content();
        $data = json_decode($json, true);

        expect($data['found'])->toBeTrue();
        expect($data['name'])->toBe('Test User');

        $req2 = Mockery::mock(Request::class);
        $req2->shouldReceive('validate')->once();
        $req2->shouldReceive('get')->with('phone')->andReturn('0');
        $factory = $tool->handle($req2);
        $data = json_decode((string) $factory->responses()->first()->content(), true);
        expect($data['found'])->toBeFalse();
    });

    it('handles get-conversation-history tool and resource', function () {
        $phone = '628123456789';

        $incoming = WaMessageWebhookLog::create([
            'message_from' => $phone,
            'message_body' => 'hello',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $outgoing = WaMessageSentLog::create([
            'recipient_number' => $phone,
            'message_content' => 'reply',
            'success' => true,
            'response_data' => [],
        ]);

        WaApiMessageThreads::create([
            'phone_number' => $phone,
            'messageable_id' => $incoming->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now()->subMinute(),
        ]);

        WaApiMessageThreads::create([
            'phone_number' => $phone,
            'messageable_id' => $outgoing->id,
            'messageable_type' => WaMessageSentLog::class,
            'last_message_at' => now(),
        ]);

        $req = Mockery::mock(Request::class);
        $req->shouldReceive('validate')->once();
        $req->shouldReceive('get')->with('phone')->andReturn($phone);
        $req->shouldReceive('get')->with('limit', 10)->andReturn(10);

        $tool = new GetConversationHistoryTool;
        $factory = $tool->handle($req);
        $data = json_decode((string) $factory->responses()->first()->content(), true);
        expect($data['phone'])->toBe($phone);
        expect($data['total_messages'])->toBeGreaterThan(0);

        $resource = new ConversationHistoryResource($phone, 10);
        $md = $resource->handle();
        expect($md)->toContain('Conversation History');
        expect($resource->mimeType())->toBe('text/markdown');
    });

    it('handles products tool and schemas', function () {
        $req = Mockery::mock(Request::class);
        $req->shouldReceive('validate')->once();
        $req->shouldReceive('get')->with('user_id')->andReturn(null);
        $req->shouldReceive('get')->with('category')->andReturn(null);
        $req->shouldReceive('get')->with('limit', 10)->andReturn(10);

        $tool = new GetProductsTool;
        $factory = $tool->handle($req);
        $data = json_decode((string) $factory->responses()->first()->content(), true);
        expect($data['total'])->toBe(0);

        $schema = Mockery::mock(JsonSchema::class);
        $type = Mockery::mock();
        $type->shouldReceive('description')->andReturnSelf();
        $type->shouldReceive('required')->andReturnSelf();
        $type->shouldReceive('min')->andReturnSelf();
        $type->shouldReceive('max')->andReturnSelf();
        $type->shouldReceive('nullable')->andReturnSelf();
        $schema->shouldReceive('string')->andReturn($type);
        $schema->shouldReceive('integer')->andReturn($type);
        $schema->shouldReceive('boolean')->andReturn($type);
        $schema->shouldReceive('array')->andReturn($type);

        expect($tool->schema($schema))->toBeArray();
        expect($tool->outputSchema($schema))->toBeArray();
    });

    it('builds support agent prompt text with context injection', function () {
        AiModelInstruction::create([
            'name' => 'Default',
            'key' => 'whatsapp_default',
            'instructions' => 'Hello {{name}}',
            'enabled' => true,
            'scope' => null,
        ]);

        $req = Mockery::mock(Request::class);
        $req->shouldReceive('validate')->once();
        $req->shouldReceive('get')->with('key', Mockery::any())->andReturn('whatsapp_default');
        $req->shouldReceive('get')->with('context', [])->andReturn(['name' => 'John']);

        $prompt = new SupportAgentPrompt;
        $res = $prompt->handle($req);
        expect((string) $res->content())->toContain('Hello John');
    });

    it('returns no-instruction message when key not found in DB', function () {
        $req = Mockery::mock(Request::class);
        $req->shouldReceive('validate')->once();
        $req->shouldReceive('get')->with('key', Mockery::any())->andReturn('nonexistent_key');
        $req->shouldReceive('get')->with('context', [])->andReturn([]);

        $prompt = new SupportAgentPrompt;
        $res = $prompt->handle($req);
        expect((string) $res->content())->toContain('No AI instructions found');
    });

    it('returns SupportAgentPrompt arguments definition', function () {
        $prompt = new SupportAgentPrompt;
        $args = $prompt->arguments();
        expect($args)->toBeArray()->toHaveKey('key')->toHaveKey('context');
    });

    it('exposes GetUserByPhoneTool schema and outputSchema', function () {
        $schema = Mockery::mock(JsonSchema::class);
        $type = Mockery::mock();
        $type->shouldReceive('description')->andReturnSelf();
        $type->shouldReceive('required')->andReturnSelf();
        $type->shouldReceive('nullable')->andReturnSelf();
        $schema->shouldReceive('string')->andReturn($type);
        $schema->shouldReceive('boolean')->andReturn($type);

        $tool = new GetUserByPhoneTool;
        expect($tool->schema($schema))->toBeArray();
        expect($tool->outputSchema($schema))->toBeArray();
    });

    it('exposes GetConversationHistoryTool schema and outputSchema', function () {
        $schema = Mockery::mock(JsonSchema::class);
        $type = Mockery::mock();
        $type->shouldReceive('description')->andReturnSelf();
        $type->shouldReceive('required')->andReturnSelf();
        $type->shouldReceive('min')->andReturnSelf();
        $type->shouldReceive('max')->andReturnSelf();
        $schema->shouldReceive('string')->andReturn($type);
        $schema->shouldReceive('integer')->andReturn($type);
        $schema->shouldReceive('array')->andReturn($type);

        $tool = new GetConversationHistoryTool;
        expect($tool->schema($schema))->toBeArray();
        expect($tool->outputSchema($schema))->toBeArray();
    });
});
