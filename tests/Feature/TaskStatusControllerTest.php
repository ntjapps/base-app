<?php

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = User::factory()->create(['username' => 'u1']);
    $this->other = User::factory()->create(['username' => 'u2']);
    $this->super = User::factory()->create(['username' => 'super']);

    Gate::define('hasSuperPermission', function ($user, $model = null) {
        return $user->username === 'super';
    });
});

test('task status returns 404 when missing', function () {
    Passport::actingAs($this->user);
    $response = $this->getJson('/api/v1/tasks/not-a-real-id');
    $response->assertStatus(404);
});

test('task status returns 403 when owned by another user', function () {
    $task = TaskStatus::create([
        'task_name' => 't1',
        'idempotency_key' => 'k1',
        'queue' => 'q1',
        'status' => 'queued',
        'payload' => [],
        'attempt' => 0,
        'max_attempts' => 1,
        'user_id' => $this->other->id,
    ]);

    Passport::actingAs($this->user);
    $response = $this->getJson('/api/v1/tasks/'.$task->id);
    $response->assertStatus(403);
    $response->assertJsonPath('message', 'Unauthorized to view this task');
});

test('super user can view other users tasks', function () {
    $task = TaskStatus::create([
        'task_name' => 't2',
        'idempotency_key' => 'k2',
        'queue' => 'q1',
        'status' => 'completed',
        'payload' => [],
        'result' => ['ok' => true],
        'attempt' => 0,
        'max_attempts' => 1,
        'user_id' => $this->other->id,
    ]);

    Passport::actingAs($this->super);
    $response = $this->getJson('/api/v1/tasks/'.$task->id);
    $response->assertStatus(200);
    $response->assertJsonPath('id', $task->id);
});

test('task list is filtered for non-super users', function () {
    TaskStatus::create([
        'task_name' => 't3',
        'idempotency_key' => 'k3',
        'queue' => 'q1',
        'status' => 'queued',
        'payload' => [],
        'attempt' => 0,
        'max_attempts' => 1,
        'user_id' => $this->user->id,
    ]);
    TaskStatus::create([
        'task_name' => 't4',
        'idempotency_key' => 'k4',
        'queue' => 'q1',
        'status' => 'queued',
        'payload' => [],
        'attempt' => 0,
        'max_attempts' => 1,
        'user_id' => $this->other->id,
    ]);

    Passport::actingAs($this->user);
    $res = $this->getJson('/api/v1/tasks');
    $res->assertStatus(200);
    $ids = collect($res->json())->pluck('id')->all();
    expect($ids)->toHaveCount(1);
});

test('task list returns all tasks for super users and supports filters', function () {
    TaskStatus::create([
        'task_name' => 't5',
        'idempotency_key' => 'k5',
        'queue' => 'q1',
        'status' => 'queued',
        'payload' => [],
        'attempt' => 0,
        'max_attempts' => 1,
        'user_id' => $this->user->id,
    ]);
    $match = TaskStatus::create([
        'task_name' => 't6',
        'idempotency_key' => 'k6',
        'queue' => 'q1',
        'status' => 'completed',
        'payload' => [],
        'attempt' => 0,
        'max_attempts' => 1,
        'user_id' => $this->other->id,
    ]);

    Passport::actingAs($this->super);
    $res = $this->getJson('/api/v1/tasks?status=completed&task_name=t6');
    $res->assertStatus(200);
    $ids = collect($res->json())->pluck('id')->all();
    expect($ids)->toBe([$match->id]);
});

test('task list validates inputs', function () {
    Passport::actingAs($this->super);
    $long = str_repeat('a', 51);
    $res = $this->getJson('/api/v1/tasks?status='.$long);
    $res->assertStatus(422);
});
