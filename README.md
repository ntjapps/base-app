# This is base application template

## Run this template

`docker compose -f .github/docker-dev.yaml up`

## Worker Backend Configuration

This application supports multiple worker backend options for processing asynchronous tasks via RabbitMQ:

### Available Worker Backends

1. **Celery (Python)** - Traditional Celery worker backend
2. **Go Worker** - Modern Go-based worker implementation (see [base-go-app](https://github.com/ntjapps/base-go-app))
3. **Both** - Send tasks to both Celery and Go workers simultaneously

### Configuration

Set the worker backend type in your `.env` file:

```env
RABBITMQ_ENABLED=true
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=queueuser
RABBITMQ_PASSWORD=queuepass
RABBITMQ_VHOST=queuevhost
WORKER_BACKEND=celery  # Options: celery, go, both
```

### Implementation Details

#### Using Celery Worker Backend

The Celery backend uses the traditional Celery v2 message protocol. Tasks are sent using the `CeleryFunction` trait.

**Setup:**
1. Install and configure RabbitMQ
2. Set up Celery worker (Python-based)
3. Set `WORKER_BACKEND=celery` in `.env`

#### Using Go Worker Backend

The Go worker backend uses a modern JSON-based task envelope format and is optimized for performance.

**Setup:**
1. Clone and deploy the [base-go-app](https://github.com/ntjapps/base-go-app) worker
2. Configure the same RabbitMQ connection settings
3. Set `WORKER_BACKEND=go` in `.env`

The Go worker provides:
- Optimized RabbitMQ consumer
- PostgreSQL persistence using GORM
- Health check endpoints for monitoring
- Docker support with multi-stage builds

#### Using Both Workers

You can send tasks to both backends simultaneously for migration or redundancy purposes.

**Setup:**
1. Set up both Celery and Go workers
2. Set `WORKER_BACKEND=both` in `.env`

**Note:** When using both backends, tasks will be sent to both workers. Ensure your database can handle duplicate operations or that your tasks are idempotent.

### Task Format

#### Celery Format (Legacy)
```php
$this->sendTask('task_name', [json_encode($data)], 'queue_name');
```

#### Go Worker Format (New)
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
