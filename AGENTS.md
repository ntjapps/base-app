# NTJ Ecosystem

## Architecture

- GET handled by Laravel controllers with Redis caching.
- Mutations handled by Go workers via `sendGoTask()`:
    - Validate and enqueue.
    - Return `202 Accepted` with `task_id`.
    - Worker updates `task_statuses`.
    - Frontend polls `/api/v1/tasks/{task_id}`.
- Worker beacon: Redis key `system:go_worker:beacon` every 30s.
- Idempotency: SHA-256 key in payload.

## Stack

- Laravel gateway: PHP 8.5, Laravel 12, Vue 3 + TS + Vite 7, PrimeVue 4 (unstyled) + Tailwind 4, Pest + Vitest, RabbitMQ + NATS.
- Go workers: Go 1.25.5, GORM (Postgres), NATS JetStream primary + RabbitMQ fallback, `go test` + miniredis + sqlite.

## Config

- Precedence: cached config > `.env` > OS env.
- Queue & messaging: `NATS_ENABLED=true`, `NATS_HOST=nats`, `NATS_PORT=4222`, `NATS_STREAM_NAME=TASKS`, `WORKER_BACKEND=nats`, `RABBITMQ_HOST=rabbitmq`.
- Database & cache: `REDIS_ADDR=redis:6379`, `DB_HOST=postgres`.
- App & AI: `AI_ASSISTANT_NAME`, `PUSHER_HOST=sockudo`, `WEBHOOK_OAUTH_CLIENT_ID`.
- Worker tuning: `HEALTH_PORT=8080`, `WORKER_CONCURRENCY=10`, `BACKOFF_ENABLED=true`, `DRY_RUN=false`.

## Go Tasks

- Queues: `admin`, `whatsapp`, `logger`, `celery`.
- Admin: `user_create_or_update`, `user_delete`, `user_reset_password`, `role_create_or_update`, `role_delete`, `division_create_or_update`, `division_delete`, `oauth_client_create`, `oauth_client_update`, `oauth_client_delete`, `oauth_client_reset_secret`, `instruction_create_or_update`, `instruction_delete`.
- WhatsApp: `wa-inbound`, `wa-send-message`, `wa-send-template-message`, `conversation-claim`, `conversation-resolve`, `conversation-tags-add`, `conversation-tags-remove`, `template-create`, `template-update`, `template-delete`, `human-handover`, `agent-assignment`.
- System: `log_agent` (`tasks.logger`), `App\Jobs\InvalidateGoCacheJob`, `logger`, `notification_mark_read`, `notification_clear_all`.

## Guidelines

- Go tasks: JSON-tagged payload struct, `TaskHandler.Handle`, `RegisterTask`, `IdempotencyKey`, GORM only.
- Go tests: miniredis via `testutils.SetupTestRedis`, sqlite in-memory via `testutils.SetupTestDB`, no real Redis/Postgres/NATS, run `go run cmd/validate-tests/main.go`, non-test code uses central config.
- Laravel: `declare(strict_types=1);`, `GoWorkerFunction` + `sendGoTask()`, WhatsApp MCP in `App\Mcp\Servers\WhatsAppServer`, frontend scripts with `bun run <script>`.

## Ops

- Startup: Laravel `php artisan serve` or `composer dev`, Go worker `go run cmd/worker/main.go`, frontend `bun run dev`.
- Health: `http://localhost:{HEALTH_PORT}/healthcheck` returns DB/NATS/RabbitMQ status.
- Docs: No auto `.md` creation; update this file.
