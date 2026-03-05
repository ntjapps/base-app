<?php

namespace Tests\Feature;

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Test getting task status by ID
     */
    public function test_can_get_task_status(): void
    {
        $task = TaskStatus::create([
            'task_name' => 'test_task',
            'idempotency_key' => 'test_key_123',
            'queue' => 'admin',
            'status' => 'queued',
            'payload' => ['test' => 'data'],
            'queued_at' => now(),
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'task_name',
                'status',
                'queue',
                'attempt',
                'max_attempts',
                'queued_at',
                'is_terminal',
                'is_pending',
            ])
            ->assertJson([
                'id' => $task->id,
                'task_name' => 'test_task',
                'status' => 'queued',
            ]);
    }

    /**
     * Test getting task list
     */
    public function test_can_get_task_list(): void
    {
        TaskStatus::create([
            'task_name' => 'task_1',
            'idempotency_key' => 'key_1',
            'queue' => 'admin',
            'status' => 'completed',
            'queued_at' => now(),
            'user_id' => $this->user->id,
        ]);

        TaskStatus::create([
            'task_name' => 'task_2',
            'idempotency_key' => 'key_2',
            'queue' => 'admin',
            'status' => 'queued',
            'queued_at' => now(),
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'task_name',
                    'status',
                    'queue',
                    'queued_at',
                    'is_terminal',
                ],
            ]);
    }

    /**
     * Test task not found returns 404
     */
    public function test_task_not_found_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/tasks/non-existent-id');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Task not found',
            ]);
    }

    /**
     * Test filter task list by status
     */
    public function test_can_filter_task_list_by_status(): void
    {
        TaskStatus::create([
            'task_name' => 'task_queued',
            'idempotency_key' => 'key_q',
            'queue' => 'admin',
            'status' => 'queued',
            'queued_at' => now(),
            'user_id' => $this->user->id,
        ]);

        TaskStatus::create([
            'task_name' => 'task_completed',
            'idempotency_key' => 'key_c',
            'queue' => 'admin',
            'status' => 'completed',
            'queued_at' => now(),
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/tasks?status=queued');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'queued');
    }

    /**
     * Test unauthenticated request returns 401
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(401);
    }
}
