---
name: laravel-vue-api-gateway
description: "USE FOR: implementing Laravel+Vue features in main-site. Covers creating controllers, routes, Go task dispatch (sendGoTask), Eloquent models, Pest tests, Vue components, Pinia stores, and TypeScript API clients. Keywords: controller, route, api, sendGoTask, GoWorkerFunction, GoQueues, task, payload, 202, Vue, Pinia, Pest, model, policy, job, service, migration, validation."
---

# Laravel + Vue API Gateway Skill

## Overview

This skill governs all feature work in **main-site** (PHP/Laravel backend + Vue 3/TypeScript frontend). Mutations go through Go workers via `sendGoTask()`; reads are handled by Laravel controllers with Redis caching.

---

## 1. Controller Conventions

- `declare(strict_types=1);` at top of every PHP file.
- Use traits: `GoWorkerFunction`, `JsonResponse`, `LogContext`.
- Always resolve the authenticated user: `Auth::user() ?? Auth::guard('api')->user()`.
- Log every request entry with `Log::info` and task dispatch with `Log::notice`.
- Validate inline with `Validator::make()` + throw `ValidationException` on failure.
- For **reads**: query with Eloquent, apply Redis caching where appropriate, return `200 OK`.
- For **mutations**: call `sendGoTask()`, return `202 Accepted` with `task_id`.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\GoQueues;
use App\Traits\GoWorkerFunction;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ExampleController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    public function getExampleList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested example list', $this->getLogContext($request, $user));

        $data = \App\Models\Example::all();

        return response()->json(['data' => $data]);
    }

    public function postExampleSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested example submit', $this->getLogContext($request, $user));

        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'id'          => ['required_if:type_create,false', 'string', 'exists:App\Models\Example,id'],
            'name'        => ['required', 'string', 'max:255'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $taskId = $this->sendGoTask(
            task: 'example_create_or_update',
            payload: [
                'type_create'  => $validated['type_create'],
                'id'           => $validated['id'] ?? null,
                'name'         => $validated['name'],
                'requested_by' => $user?->id ?? null,
            ],
            queue: GoQueues::ADMIN,
        );

        Log::notice('Enqueued example task', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status'  => 'queued',
            'message' => 'Example task has been queued',
        ], 202);
    }

    public function postDeleteExampleSubmit(Request $request, \App\Models\Example $example): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested example delete', $this->getLogContext($request, $user));

        $taskId = $this->sendGoTask(
            task: 'example_delete',
            payload: ['id' => $example->id, 'requested_by' => $user?->id ?? null],
            queue: GoQueues::ADMIN,
        );

        Log::notice('Enqueued example delete task', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status'  => 'queued',
            'message' => 'Example delete task has been queued',
        ], 202);
    }
}
```

---

## 2. Route Conventions

All API routes live in `routes/api.php` under the `v1` prefix, wrapped in `XssProtection` middleware.

- Route **naming**: `get-<resource>-list`, `post-<resource>-submit`, `post-update-<resource>-submit`, `post-delete-<resource>-submit`, `post-reset-*`, `post-restore-<resource>-submit`.
- **Auth guard**: `auth:api` for all protected routes.
- **Permission gates**: `can:hasSuperPermission,App\Models\User` for super-admin, `can:hasPermission,<perm.key>` for scoped permissions.
- **Rate limiting**: `throttle:10,1` on auth endpoints.
- **Route model binding**: use typed model parameters (e.g., `Example $example`) for delete/patch routes.

```php
Route::prefix('v1')->middleware([XssProtection::class])->group(function () {
    Route::middleware(['auth:api'])->group(function () {

        // Public (any authenticated user)
        Route::prefix('examples')->group(function () {
            Route::get('/', [ExampleController::class, 'getExampleList'])->name('get-example-list');
        });

        // Super-admin only
        Route::middleware(['can:hasSuperPermission,App\Models\User'])->group(function () {
            Route::prefix('examples')->group(function () {
                Route::post('/', [ExampleController::class, 'postExampleSubmit'])->name('post-example-submit');
                Route::patch('/{example}', [ExampleController::class, 'postUpdateExampleSubmit'])->name('post-update-example-submit');
                Route::delete('/{example}', [ExampleController::class, 'postDeleteExampleSubmit'])->name('post-delete-example-submit');
                Route::post('/{example}/restore', [ExampleController::class, 'postRestoreExampleSubmit'])->name('post-restore-example-submit');
            });
        });

        // Feature-permission scoped
        Route::middleware(['can:hasPermission,example.manage'])->group(function () {
            Route::prefix('examples')->group(function () {
                Route::patch('/{example}/status', [ExampleController::class, 'postExampleStatusSubmit'])->name('post-example-status-submit');
            });
        });
    });
});
```

---

## 3. sendGoTask() Usage

`sendGoTask()` is provided by the `GoWorkerFunction` trait. Always import `GoQueues` from `App\Interfaces\GoQueues`.

```php
use App\Interfaces\GoQueues;

$taskId = $this->sendGoTask(
    task: 'task_name_here',          // Must match a registered Go task handler
    payload: [                        // JSON-serializable, snake_case keys
        'field'        => $value,
        'requested_by' => $user?->id,
    ],
    queue: GoQueues::ADMIN,           // see GoQueues constants below
);
// Returns string UUID; always use 202 response
```

**Available queues** — see `app/Interfaces/GoQueues.php` for the full list. Common pattern:
- `GoQueues::ADMIN` (`'tasks.admin'`) – administrative management tasks
- `GoQueues::CELERY` (`'celery'`) – general/default tasks (default)
- `GoQueues::DEFAULT` (`'tasks.default'`) – fallback default queue

> Other domain-specific queue constants are defined in `GoQueues`. Use the constant that matches the task category.

**Registered task names** — use the exact identifier registered in the Go worker. The task name must match the string passed to `RegisterTask` in the worker codebase. Refer to the project's Go task registry for the complete list.

---

## 4. Model Conventions

- Always `use HasUuids;` — all PKs are UUIDs.
- Use `$fillable` (never `$guarded`).
- Use `$casts` for booleans, dates, arrays, encrypted fields.
- Implement cache invalidation in `boot()` by dispatching `InvalidateGoCacheJob`.
- Use `SoftDeletes` + `Prunable` on user-facing models.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\InvalidateGoCacheJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Example extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'description', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(fn ($model) => InvalidateGoCacheJob::dispatch('example'));
        static::deleted(fn ($model) => InvalidateGoCacheJob::dispatch('example'));
    }
}
```

---

## 5. Pest Test Conventions

- Test files: `tests/Unit/` or `tests/Feature/`.
- Use Pest syntax: `it('does something', function () { ... });` or `test(...)`.
- Mock HTTP with `Http::fake([...])`.
- Mock Redis/Cache with `Cache::shouldReceive(...)` or in-memory drivers.
- Never hit real DB, Redis, NATS, or RabbitMQ in unit tests.
- Feature tests use `RefreshDatabase` trait and SQLite in-memory.

```php
<?php

use App\Http\Controllers\ExampleController;
use Illuminate\Support\Facades\Http;

it('returns 202 when submitting example task', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/examples', [
            'type_create' => true,
            'name'        => 'Test Example',
        ])
        ->assertStatus(202)
        ->assertJsonStructure(['task_id', 'status', 'message']);
});

it('validates required fields', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/examples', [])
        ->assertStatus(422);
});
```

---

## 6. Vue + TypeScript Conventions

### API Client (`AppAxios.ts`)

Every API method lives in the `ApiClient` class. Method naming mirrors route names:
- `getExampleList()` → `GET /api/v1/examples`
- `postExampleSubmit(data)` → `POST /api/v1/examples`
- `postUpdateExampleSubmit(id, data)` → `PATCH /api/v1/examples/{id}`
- `postDeleteExampleSubmit(id)` → `DELETE /api/v1/examples/{id}`
- `postRestoreExampleSubmit(id)` → `POST /api/v1/examples/{id}/restore`

```typescript
async getExampleList(options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse<Example[]>>> {
    return this.get('/api/v1/examples', options);
}

async postExampleSubmit(data: Record<string, unknown>, options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
    return this.post('/api/v1/examples', data, options);
}

async postUpdateExampleSubmit(id: string, data: Record<string, unknown>, options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
    return this.patch(`/api/v1/examples/${id}`, data, options);
}

async postDeleteExampleSubmit(id: string, options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
    return this.delete(`/api/v1/examples/${id}`, options);
}

async postRestoreExampleSubmit(id: string, options: RequestOptions = {}): Promise<AxiosResponse<ApiResponse>> {
    return this.post(`/api/v1/examples/${id}/restore`, {}, options);
}
```

### Handling 202 Accepted

Always call `api.handle202Accepted(response, 'message')` after a mutation. Close dialogs immediately — do not wait for task completion.

```typescript
const submitExample = async () => {
    try {
        const response = await api.postExampleSubmit({
            type_create: props.dialogTypeCreate,
            id: props.dialogData?.id,
            name: nameData.value,
        });
        api.handle202Accepted(response, 'Example task queued for processing');
        emit('closeDialog');
    } catch {
        // Errors handled globally by ApiClient
    }
};
```

### Components

- Use `<script setup lang="ts">` (Composition API).
- `defineProps<{...}>()` with strict TypeScript interfaces.
- `defineEmits<{...}>()` for typed events.
- Use PrimeVue 4 (unstyled) components + Tailwind 4 for styling.
- Never import CSS directly; use Tailwind utility classes.

### Pinia Store

```typescript
import { defineStore } from 'pinia';
import { api } from './AppAxios';

export const useExampleStore = defineStore('example', {
    state: () => ({
        items: [] as Example[],
        loading: false,
    }),
    actions: {
        async fetchItems() {
            this.loading = true;
            try {
                const response = await api.getExampleList();
                this.items = response.data?.data ?? [];
            } finally {
                this.loading = false;
            }
        },
    },
});
```

---

## 7. Task Status Polling (Frontend)

When you need to show the result of an async Go task, poll `/api/v1/tasks/{task_id}`:

```typescript
const pollTaskStatus = async (taskId: string, maxAttempts = 20, intervalMs = 1500) => {
    for (let i = 0; i < maxAttempts; i++) {
        const response = await api.getTaskStatus(taskId);
        const status = (response.data as any)?.data?.status;
        if (status === 'completed') return response.data;
        if (status === 'failed') throw new Error((response.data as any)?.data?.error_message);
        await new Promise(r => setTimeout(r, intervalMs));
    }
    throw new Error('Task timed out');
};
```

---

## 8. Redis Caching (GET endpoints)

Use `Cache::remember()` for expensive reads. Key convention: `<resource>:<identifier>` or `<resource>:list`.

```php
$data = Cache::remember('examples:list', now()->addMinutes(10), fn () =>
    \App\Models\Example::all()
);
```

Invalidate from Jobs (`InvalidateGoCacheJob`) or directly after model events.

---

## 9. Authorization (Policies)

Register policies in `AuthServiceProvider`. Use `can:hasSuperPermission` or `can:hasPermission,<key>` in route middleware. Policy method naming: `viewAny`, `view`, `create`, `update`, `delete`.

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Example;
use App\Models\User;

class ExamplePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('example.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('example.manage');
    }
}
```

---

## 10. Events & Broadcasting

All events implement `ShouldBroadcast` and broadcast via a Pusher-compatible WebSocket server on `PrivateChannel`. They signal the **frontend to refresh** — they carry no business logic.

**Conventions:**
- Traits: `Dispatchable`, `InteractsWithSockets`, `SerializesModels`
- Constructor: empty `{}` for signal-only events; typed public properties when payload is needed (i.e., when the frontend needs data immediately)
- `broadcastWith()`: return `[]` for signal-only; return the payload array when data must reach the frontend
- `declare(strict_types=1);` at the top of every event file
- Dispatch from model `boot()` observers or from Jobs after Go task completion

**Channel mapping (all `PrivateChannel`):**

| Channel | Events |
|---------|--------|
| `resource.event` | `ResourceCreated`, `ResourceUpdated`, `ResourceDeleted` |

> Group related events on a shared channel per feature area. Reuse an existing channel when adding events for the same resource group. See `routes/channels.php` for all registered broadcast channels.

**Signal-only event (most admin events):**

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WidgetCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct() {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('resource.event')];
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
```

**Event with payload (when frontend needs the data immediately):**

```php
class WidgetUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $widget) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('resource.event')];
    }

    public function broadcastWith(): array
    {
        return ['widget' => $this->widget];
    }
}
```

**Dispatching events** — from a model's `boot()` or a Job:

```php
// In model boot()
static::saved(function ($model) {
    WidgetCreated::dispatch();
    InvalidateGoCacheJob::dispatch('widget');
});

// With payload
static::saved(function ($model) {
    WidgetUpdated::dispatch($model->toArray());
});
```

**Frontend — listening for broadcast events** (Vue page/component pattern):

Real-time subscriptions use `useEchoStore()` from `AppState.ts`. Subscribe in `onMounted`, unsubscribe in `onUnmounted`, always wrap in try/catch (Echo may not be initialized on SSR or in tests).

```typescript
import { onMounted, onUnmounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useEchoStore } from '../AppState';
import type { EchoWithMethods } from '../types/echo';

const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);

onMounted(() => {
    fetchWidgets();

    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const ch = echoInstance.private('resource.event');
            ch.listen('WidgetCreated', () => fetchWidgets());
            ch.listen('WidgetUpdated', () => fetchWidgets());
            ch.listen('WidgetDeleted', () => fetchWidgets());
        }
    } catch (err) {
        console.debug('Echo private channel not available during mount.', err);
    }
});

onUnmounted(() => {
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.leave === 'function') {
            echoInstance.leave('resource.event');
        }
    } catch (err) {
        console.debug('Echo leave failed during unmount.', err);
    }
});
```

**`useEchoStore`** is defined in `AppState.ts` and initialises Laravel Echo with the Pusher-compatible WebSocket connection. Call `echo.initEcho()` once at app startup (in `App.vue` or the root component) before any page tries to subscribe.

**New event checklist:**
- [ ] `app/Events/<Resource><Action>.php` — signal-only or with payload
- [ ] `declare(strict_types=1);` at top
- [ ] Correct `PrivateChannel` name (reuse existing channel if resource fits an existing group)
- [ ] Dispatch from model `boot()` or Job
- [ ] Frontend listener in composable or component `onMounted`

---

## 11. Notifications

`MessageNotification` is the single notification class. It delivers via two channels simultaneously: **broadcast** (WebSocket → frontend toast) and **database** (stored in `notifications` table for the inbox).

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public ?string $message = null,
        public string $severityType = 'info'   // 'info' | 'success' | 'error' | 'warn'
    ) {
        $this->afterCommit();  // Fire after DB transaction commits
    }

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'summary'  => $this->title,
            'message'  => $this->message,
            'severity' => $this->severityType,
        ];
    }
}
```

**Sending a notification** to a user from a controller or job:

```php
use Illuminate\Support\Facades\Notification;

// Preferred: sendNow() bypasses queue — use when inside a queued job/after commit context
Notification::sendNow($user, new MessageNotification('Title', 'Detail message', 'success'));

// Async (queued):
$user->notify(new MessageNotification('Title', 'Something happened', 'info'));
```

The Go dispatcher fires the equivalent broadcast automatically for every task. Only call `MessageNotification` from PHP code when you need to send a notification outside the Go task flow (e.g., from a scheduled command or non-Go-task controller action).

---

## 12. Custom Validation Rules

Custom rules live in `app/Rules/` and implement `ValidationRule`:

```php
<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExampleRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, ['allowed', 'values'])) {
            $fail('The :attribute must be one of: allowed, values.');
        }
    }
}
```

Use in inline controller validation:

```php
$validate = Validator::make($request->all(), [
    'platform' => ['required', 'string', new \App\Rules\ExampleRule()],
]);
```

---

## 13. Feature Flags (Laravel Pennant)

Feature classes live in `app/Features/`. Each class has a single `resolve(User $user): bool` method. Use for gating UI features or controller logic.

```php
<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class NewDashboard
{
    public function resolve(User $user): bool
    {
        return match (true) {
            Gate::forUser($user)->allows('hasSuperPermission', User::class) => true,
            config('app.debug') => true,
            default => false,
        };
    }
}
```

Check in a controller:

```php
use Laravel\Pennant\Feature;

if (Feature::for($user)->active(\App\Features\NewDashboard::class)) {
    // show new UI
}
```

---

## 14. Checklist for New Feature

Use this when adding a new resource (e.g., "widget management"):

- [ ] **Migration**: `php artisan make:migration create_widgets_table` — UUID PK, standard timestamps
- [ ] **Model**: `app/Models/Widget.php` — `HasUuids`, `$fillable`, `$casts`, boot cache invalidation
- [ ] **Policy**: `app/Policies/WidgetPolicy.php` — register in `AuthServiceProvider`
- [ ] **Controller**: `app/Http/Controllers/WidgetManController.php` — `GoWorkerFunction`, `LogContext`, `JsonResponse` traits
- [ ] **Routes**: `routes/api.php` — under correct permission middleware, named `get-widget-*`, `post-widget-*`, `post-update-widget-*`, `post-delete-widget-*`; add `post-restore-widget-*` if soft-delete is needed
- [ ] **Go task names**: coordinate with Go worker (use the exact task identifier registered in the worker's task registry)
- [ ] **Events**: `app/Events/Widget{Created,Updated,Deleted}.php` — `ShouldBroadcast`, correct `PrivateChannel`, dispatch from model boot
- [ ] **API client methods**: add to `ApiClient` in `AppAxios.ts`
- [ ] **Vue component**: `resources/js/components/` — typed props/emits, 202 handling
- [ ] **Pinia store** (if needed): `resources/js/stores/`
- [ ] **Frontend broadcast listener**: composable or component `onMounted` for live updates
- [ ] **Pest tests**: feature test for controller (validation, 202 response, auth), unit test for complex logic
- [ ] **`declare(strict_types=1);`** on every PHP file