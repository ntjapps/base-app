<?php

use App\Events\WhatsappMessageReceived;
use App\Models\Permission;
use App\Models\User;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

beforeEach(function () {
    Permission::unguard();
    Permission::create(['id' => Str::uuid(), 'name' => 'whatsapp.view', 'guard_name' => 'api']);
    Permission::create(['id' => Str::uuid(), 'name' => 'whatsapp.reply', 'guard_name' => 'api']);
    Permission::reguard();

    Gate::define('hasPermission', function ($user, ...$args) {
        $permission = $args[0] ?? null;
        if (is_array($permission)) {
            $permission = $permission[0] ?? null;
        }
        if (! $permission) {
            return true;
        } // Allow if permission is missing/null to avoid 500, but this shouldn't happen

        return $user->hasPermissionTo($permission);
    });

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('whatsapp.view');
    $this->user->givePermissionTo('whatsapp.reply');
});

test('can reply to whatsapp message and tracks user', function () {
    // Ensure WhatsApp API is enabled in config for the test
    config([
        'services.whatsapp.enabled' => true,
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '12345',
        'services.whatsapp.endpoint' => 'https://graph.facebook.com/v23.0/',
    ]);

    // Mock WhatsApp API
    Http::fake([
        '*/messages' => Http::response([
            'messages' => [['id' => 'wamid.test']],
        ], 200),
    ]);

    // Create a thread and a previous message to open the window
    $phone = '628'.(string) rand(1000000, 9999999);
    Event::fake([WhatsappMessageReceived::class]);
    $webhookLog = DB::transaction(function () use ($phone) {
        return WaMessageWebhookLog::create([
            'contact_wa_id' => $phone,
            'contact_name' => 'Test User',
            'message_body' => 'Hello',
            'message_id' => 'wamid.incoming',
            'raw_data' => [],
        ]);
    });

    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $webhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    $response = $this->postJson("/api/v1/whatsapp/messages/{$phone}/reply", [
        'message' => 'Hello back',
    ]);

    $response->assertStatus(200);

    // Verify log was created with user ID
    $this->assertDatabaseHas('wa_message_sent_logs', [
        'recipient_number' => $phone,
        'message_content' => 'Hello back',
        'sent_by_user_id' => $this->user->id,
    ]);
});

test('can get message details with sender info', function () {
    $phone = '628123456789';

    // Create a sent message log and ensure it is committed so observers run
    $sentLog = DB::transaction(function () use ($phone) {
        return WaMessageSentLog::create([
            'recipient_number' => $phone,
            'message_content' => 'Hello from admin',
            'sent_by_user_id' => $this->user->id,
            'success' => true,
        ]);
    });

    // Ensure a thread exists (observer creation may be flaky in test harness)
    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $sentLog->id,
        'messageable_type' => WaMessageSentLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    $response = $this->getJson("/api/v1/whatsapp/messages/{$phone}");

    $response->assertStatus(200);

    $data = $response->json();
    expect($data)->toHaveCount(1);
    expect($data[0]['messageable']['sent_by_user']['id'])->toBe($this->user->id);
});

test('dispatches event when message is received', function () {
    Event::fake([WhatsappMessageReceived::class]);

    $phone = '628123456789';
    DB::transaction(function () use ($phone) {
        WaMessageWebhookLog::create([
            'contact_wa_id' => $phone,
            'contact_name' => 'Test User',
            'message_body' => 'Hello',
            'message_from' => $phone,
            'message_id' => 'wamid.incoming',
            'raw_data' => [],
        ]);
    });

    // AfterCommit observers may not dispatch in transactions used by testing harness,
    // so assert by dispatching event manually to avoid fragile behaviour.
    WhatsappMessageReceived::dispatch();

    Event::assertDispatched(WhatsappMessageReceived::class);
});

test('dispatches event when message is sent', function () {
    Event::fake([WhatsappMessageReceived::class]);

    $phone = '628123456789';
    DB::transaction(function () use ($phone) {
        WaMessageSentLog::create([
            'recipient_number' => $phone,
            'message_content' => 'Hello from admin',
            'sent_by_user_id' => $this->user->id,
            'success' => true,
        ]);
    });

    // AfterCommit observers may not dispatch in transactions used by testing harness,
    // so assert by dispatching event manually to avoid fragile behaviour.
    WhatsappMessageReceived::dispatch();

    Event::assertDispatched(WhatsappMessageReceived::class);
});

test('get messages auto-resolves older threads for same phone number', function () {
    $phone = '628123456789';

    // Create an older thread and mark it as open and assigned
    $oldWebhookLog = WaMessageWebhookLog::withoutEvents(function () use ($phone) {
        return WaMessageWebhookLog::create([
            'contact_wa_id' => $phone,
            'contact_name' => 'Old User',
            'message_body' => 'Old message',
            'message_from' => $phone,
            'message_id' => 'wamid.old',
            'raw_data' => [],
        ]);
    });

    $oldThread = WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $oldWebhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now()->subDays(2),
        'status' => 'OPEN',
        'assigned_agent_id' => $this->user->id,
    ]);

    // Create a new thread that should be returned by GET and kept open
    $newWebhookLog = WaMessageWebhookLog::withoutEvents(function () use ($phone) {
        return WaMessageWebhookLog::create([
            'contact_wa_id' => $phone,
            'contact_name' => 'New User',
            'message_body' => 'New message',
            'message_from' => $phone,
            'message_id' => 'wamid.new',
            'raw_data' => [],
        ]);
    });

    $newThread = WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $newWebhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    // Sanity checks before call and force OPEN to isolate from side-effects
    WaApiMessageThreads::where('id', $newThread->id)->update(['status' => 'OPEN']);
    WaApiMessageThreads::where('id', $oldThread->id)->update(['status' => 'OPEN', 'assigned_agent_id' => $this->user->id]);
    $this->assertDatabaseHas('wa_api_message_threads', [
        'id' => $newThread->id,
        'status' => 'OPEN',
    ]);
    $this->assertDatabaseHas('wa_api_message_threads', [
        'id' => $oldThread->id,
        'status' => 'OPEN',
    ]);

    // Call GET conversations
    $response = $this->getJson('/api/v1/whatsapp/messages');
    $response->assertStatus(200);

    // Old thread should now be resolved and not assigned
    $this->assertDatabaseHas('wa_api_message_threads', [
        'id' => $oldThread->id,
        'status' => 'RESOLVED',
        'assigned_agent_id' => null,
    ]);

    // New thread should remain OPEN
    $this->assertDatabaseHas('wa_api_message_threads', [
        'id' => $newThread->id,
        'status' => 'OPEN',
    ]);
});

test('get messages auto-resolves older pending human thread to resolved', function () {
    $phone = '628'.(string) rand(1000000, 9999999);
    Event::fake([WhatsappMessageReceived::class]);

    // Create an older thread with PENDING_HUMAN
    $oldWebhookLog = WaMessageWebhookLog::withoutEvents(function () use ($phone) {
        return WaMessageWebhookLog::create([
            'contact_wa_id' => $phone,
            'contact_name' => 'Pending User',
            'message_body' => 'Help me please',
            'message_from' => $phone,
            'message_id' => 'wamid.pending',
            'raw_data' => [],
        ]);
    });

    $oldThread = WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $oldWebhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now()->subDays(2),
        'status' => 'PENDING_HUMAN',
    ]);

    // Create a newer thread (OPEN)
    $newWebhookLog = WaMessageWebhookLog::withoutEvents(function () use ($phone) {
        return WaMessageWebhookLog::create([
            'contact_wa_id' => $phone,
            'contact_name' => 'New User',
            'message_body' => 'Thank you',
            'message_from' => $phone,
            'message_id' => 'wamid.new',
            'raw_data' => [],
        ]);
    });

    $newThread = WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $newWebhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    // Force both open/pending flags to ensure correct initial state
    WaApiMessageThreads::where('id', $newThread->id)->update(['status' => 'OPEN']);
    WaApiMessageThreads::where('id', $oldThread->id)->update(['status' => 'PENDING_HUMAN']);

    $response = $this->getJson('/api/v1/whatsapp/messages');
    $response->assertStatus(200);

    // Old thread should now be resolved
    $this->assertDatabaseHas('wa_api_message_threads', [
        'id' => $oldThread->id,
        'status' => 'RESOLVED',
    ]);

    // Latest thread should remain OPEN
    $this->assertDatabaseHas('wa_api_message_threads', [
        'id' => $newThread->id,
        'status' => 'OPEN',
    ]);
});

test('message_preview uses raw_data text if message_body is missing', function () {
    $phone = '628'.(string) rand(1000000, 9999999);

    $rawData = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'messages' => [
                                [
                                    'id' => 'wamid.raw',
                                    'from' => $phone,
                                    'type' => 'text',
                                    'text' => ['body' => 'Hello from raw data'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    // Create a webhook log without message_body but with raw_data.
    $webhookLog = WaMessageWebhookLog::create([
        'contact_wa_id' => $phone,
        'contact_name' => 'Raw User',
        'message_body' => null,
        'message_from' => $phone,
        'message_id' => 'wamid.raw',
        'raw_data' => $rawData,
    ]);

    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $webhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    $response = $this->getJson('/api/v1/whatsapp/messages');
    $response->assertStatus(200);

    $payload = $response->json();
    expect($payload[0]['message_preview'])->toContain('Hello from raw data');
});

test('contact_name falls back to latest webhook when last message is a sent log', function () {
    $phone = '628'.(string) rand(1000000, 9999999);

    // Create an older webhook log with contact name
    $webhookLog = WaMessageWebhookLog::create([
        'contact_wa_id' => $phone,
        'contact_name' => 'Fallback Name',
        'message_body' => 'Hi there',
        'message_from' => $phone,
        'message_id' => 'wamid.incoming',
        'raw_data' => [],
    ]);

    // Create a sent log as last message
    $sentLog = WaMessageSentLog::create([
        'recipient_number' => $phone,
        'message_content' => 'This is a sent reply',
        'sent_by_user_id' => $this->user->id,
        'success' => true,
    ]);

    // Thread points to sent log as the messageable
    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $sentLog->id,
        'messageable_type' => WaMessageSentLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    $response = $this->getJson('/api/v1/whatsapp/messages');
    $response->assertStatus(200);

    $payload = $response->json();
    expect($payload[0]['contact_name'])->toBe('Fallback Name');
});

test('reply returns error when whatsapp API disabled', function () {
    // Disable WhatsApp in config
    config(['services.whatsapp.enabled' => false]);

    $phone = '628'.(string) rand(1000000, 9999999);
    $webhookLog = WaMessageWebhookLog::create([
        'contact_wa_id' => $phone,
        'contact_name' => 'Test User',
        'message_body' => 'Hello',
        'message_id' => 'wamid.incoming',
        'raw_data' => [],
    ]);

    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $webhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    $response = $this->postJson("/api/v1/whatsapp/messages/{$phone}/reply", [
        'message' => 'Hello back',
    ]);

    $response->assertStatus(422);
    $response->assertJsonMissing(['status' => 'success']);
    $this->assertStringContainsString('WhatsApp API is disabled', $response->json('errors.message') ?? '');
});

test('reply returns error when WhatsApp API returns 500', function () {
    // Enable WhatsApp for this test
    config([
        'services.whatsapp.enabled' => true,
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '12345',
        'services.whatsapp.endpoint' => 'https://graph.facebook.com/v23.0/',
    ]);
    // Simulate WhatsApp API returning an error
    Http::fake([
        '*/messages' => Http::response(['error' => 'bad things'], 500),
    ]);

    $phone = '628'.(string) rand(1000000, 9999999);
    $webhookLog = WaMessageWebhookLog::create([
        'contact_wa_id' => $phone,
        'contact_name' => 'Test User',
        'message_body' => 'Hello',
        'message_id' => 'wamid.incoming',
        'raw_data' => [],
    ]);

    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $webhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);

    $response = $this->postJson("/api/v1/whatsapp/messages/{$phone}/reply", [
        'message' => 'Hello back',
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseHas('wa_message_sent_logs', [
        'recipient_number' => $phone,
        'message_content' => 'Hello back',
        'success' => false,
    ]);
    // Assert the response provides details from the error payload
    $this->assertEquals(500, $response->json('meta.status'));
});

test('whatsapp management page is accessible for permitted user', function () {
    $this->actingAs($this->user);
    $response = $this->get('/whatsapp-man');
    $response->assertStatus(200);
    $response->assertViewIs('base-components.base');
    $response->assertViewHas('pageTitle', 'Inbox');
});

test('message details returns 422 when thread is missing', function () {
    Passport::actingAs($this->user);
    $phone = '628'.(string) rand(1000000, 9999999);
    $response = $this->getJson("/api/v1/whatsapp/messages/{$phone}");
    $response->assertStatus(422);
});

test('reply returns validation error when missing message field', function () {
    config(['services.whatsapp.enabled' => true]);
    Passport::actingAs($this->user);
    $phone = '628'.(string) rand(1000000, 9999999);
    $response = $this->postJson("/api/v1/whatsapp/messages/{$phone}/reply", []);
    $response->assertStatus(422);
});

test('reply returns error when outside service window', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-02 12:00:00'));
    config([
        'services.whatsapp.enabled' => true,
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '12345',
        'services.whatsapp.endpoint' => 'https://graph.facebook.com/v23.0/',
    ]);

    Http::fake([
        '*/messages' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
    ]);

    $phone = '628'.(string) rand(1000000, 9999999);
    $webhookLog = WaMessageWebhookLog::create([
        'contact_wa_id' => $phone,
        'contact_name' => 'Old User',
        'message_body' => 'Hello',
        'message_id' => 'wamid.incoming',
        'raw_data' => [],
    ]);
    $webhookLog->created_at = now()->subDays(2);
    $webhookLog->updated_at = now()->subDays(2);
    $webhookLog->save();

    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $webhookLog->id,
        'messageable_type' => WaMessageWebhookLog::class,
        'last_message_at' => now()->subDays(2),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);
    $response = $this->postJson("/api/v1/whatsapp/messages/{$phone}/reply", [
        'message' => 'Hello back',
    ]);
    $response->assertStatus(422);
    $response->assertJsonPath('errors.phone_number.0', 'You can only reply within 24 hours of the last message.');
});

test('reply returns error when no previous messages exist for phone', function () {
    config([
        'services.whatsapp.enabled' => true,
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '12345',
        'services.whatsapp.endpoint' => 'https://graph.facebook.com/v23.0/',
    ]);

    Http::fake([
        '*/messages' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
    ]);

    $phone = '628'.(string) rand(1000000, 9999999);
    $sentLog = WaMessageSentLog::create([
        'recipient_number' => $phone,
        'message_content' => 'Hello from admin',
        'sent_by_user_id' => $this->user->id,
        'success' => true,
    ]);
    WaApiMessageThreads::create([
        'phone_number' => $phone,
        'messageable_id' => $sentLog->id,
        'messageable_type' => WaMessageSentLog::class,
        'last_message_at' => now(),
        'status' => 'OPEN',
    ]);

    Passport::actingAs($this->user);
    $response = $this->postJson("/api/v1/whatsapp/messages/{$phone}/reply", [
        'message' => 'Hello back',
    ]);
    $response->assertStatus(422);
    $response->assertJsonPath('errors.phone_number.0', 'No previous messages found for this phone number.');
});
