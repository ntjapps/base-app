# This is base application template

## Run this template

`docker compose -f .github/docker-dev.yaml up`

## Worker Backend Configuration

This application supports multiple worker backend options for processing asynchronous tasks via RabbitMQ:

### Available Worker Backends

1. **Go Worker (Default)** - Modern Go-based worker implementation (see [base-go-app](https://github.com/ntjapps/base-go-app))
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
1. Clone and deploy the [base-go-app](https://github.com/ntjapps/base-go-app) worker
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

#### Celery Format (Legacy)
```php
$this->sendTask('task_name', [json_encode($data)], 'queue_name');
```

### Monitoring

The worker backend configuration is visible in the application UI:
- Navigate to Profile page when logged in
- View "Worker Backend Configuration" section
- See which backend(s) are currently active

## ENV KEY Example

`php artisan env:decrypt --key=kPwf4WgwDcV7pJH6NkheNQVKrCLXueoz`

## Removed packages (Telescope / Horizon / Pulse)

This repository has had references to Laravel Telescope, Horizon, and Pulse removed from the application code. Vendor packages are still present in `composer.lock` and under `vendor/` if you have them installed locally.

To fully remove the packages from your development environment and from `vendor/` (and to clear up published assets), run:

1. composer remove --dev laravel/telescope laravel/horizon laravel/pulse
2. composer update
3. rm -rf public/vendor/telescope public/vendor/horizon
4. php artisan optimize:clear

If you want to keep Reverb for WebSockets but disable automatic ingestion of Telescope/Pulse data, the defaults are set to 0 in `config/reverb.php`. You can re-enable by setting `REVERB_TELESCOPE_INGEST_INTERVAL` and `REVERB_PULSE_INGEST_INTERVAL` in ".env".

Run your tests to ensure no breakages: `composer test`.
