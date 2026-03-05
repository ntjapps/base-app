# This is base application template

## Run this template

`docker compose -f .github/docker-dev.yaml up`

## Worker Backend Configuration

This application supports multiple worker backend options for processing asynchronous tasks via RabbitMQ:

### Available Worker Backends

1. **Go Worker (Default)** - Modern Go-based worker implementation (see the companion `base-go-app` repository)
2. **Celery (Fallback)** - Traditional Python Celery worker backend for legacy support or specific use cases
3. **Both** - Send tasks to both Go and Celery workers simultaneously (for migration scenarios)

### Configuration

Set the worker backend type in your `.env` file:

```env
RABBITMQ_ENABLED=true
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=queueuser
RABBITMQ_PASSWORD=queuepass
RABBITMQ_VHOST=queuevhost
WORKER_BACKEND=go  # Options: go (default), celery (fallback), both
```

**Default:** `go` - The application defaults to the Go worker backend for optimal performance.

### Implementation Details

#### Using Go Worker Backend (Recommended)

The Go worker backend uses a modern JSON-based task envelope format and is optimized for performance. This is the **default and recommended** option.

**Setup:**

1. Clone and deploy the `base-go-app` worker
2. Configure the same RabbitMQ connection settings
3. Set `WORKER_BACKEND=go` in `.env` (or omit it to use the default)

The Go worker provides:

- Optimized RabbitMQ consumer
- PostgreSQL persistence using GORM
- Health check endpoints for monitoring
- Docker support with multi-stage builds
- Superior performance and lower resource usage

#### Using Celery Worker Backend (Fallback)

The Celery backend uses the traditional Celery v2 message protocol. Use this option as a fallback or for specific scenarios requiring Celery features (e.g., massive data processing with Python-specific libraries).

**Setup:**

1. Install and configure RabbitMQ
2. Set up Celery worker (Python-based)
3. Set `WORKER_BACKEND=celery` in `.env`

**Use Cases:**

- Legacy compatibility during transition period
- Specific Python library requirements
- Massive data processing scenarios where Python ecosystem is beneficial

#### Using Both Workers

You can send tasks to both backends simultaneously for migration or testing purposes.

**Setup:**

1. Set up both Go and Celery workers
2. Set `WORKER_BACKEND=both` in `.env`

**Note:** When using both backends, tasks will be sent to both workers. Ensure your database can handle duplicate operations or that your tasks are idempotent.

### Task Format

#### Go Worker Format (Default)

```php
$this->sendGoTask('task_name', $data, 'queue_name');
```

The Go worker expects tasks in the following format:

```json
{
    "version": "1.0",
    "id": "uuid",
    "task": "task_name",
    "payload": { ... },
    "created_at": "ISO8601 timestamp",
    "attempt": 0,
    "max_attempts": 5,
    "timeout_seconds": 60
}
```

### ✅ Coverage Targets

- **PHP**: ≥ 80% (current suite runs above this)
- **Frontend (TS)**: aim ≥ 70% (branches have a 50% minimum threshold)

### Writing Mutations (The Rule)

When creating a mutating endpoint (POST/PUT/DELETE):

1. Validate request in Laravel.
2. Use `sendGoTask()` to enqueue the action.
3. Return `202 Accepted` with the `task_id`.
4. Poll status via `/api/v1/tasks/{task_id}` on the frontend.

---

## 🛡️ Security & Observability

- **Secrets**: API keys are NEVER committed. Use encrypted `.env` files.
- **Idempotency**: All mutation tasks use SHA-256 hashing to prevent duplicate execution.
- **Pruning**: Old logs and webhook records are automatically pruned via the `Prunable` trait.
- **Auditing**: Every mutation task is logged with user context and payload.

---

## 🗑️ Feature Removal Guide

If using this repo as a base and needing to strip features:

1. **WhatsApp**: Set `WHATSAPP_ENABLED=false`, remove `Wa*` controllers and models.
2. **AI**: Set `AI_DEFAULT_PROVIDER=null`, remove `AiProvider*` interfaces and `app/Services/Ai`.
3. **Go Workers**: Stop using `sendGoTask` and remove `main-site-go` repo dependencies.

_For detailed removal steps, see the implementation notes in `AGENTS.md`._

---

© 2026 Base App Template. Built for scale.
