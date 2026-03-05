<?php

namespace App\Services\Tasks;

use App\Interfaces\GoWorkerTask as GoWorkerTaskInterface;
use Carbon\Carbon;

class GoWorkerTask implements GoWorkerTaskInterface
{
    protected string $id;

    protected string $task;

    protected string $idempotencyKey;

    protected array $payload;

    protected ?string $invokerId;

    protected string $createdAt;

    protected int $attempt;

    protected int $maxAttempts;

    protected ?int $timeout;

    protected $notify; // mixed, allow null or array/string

    public function __construct(string $id, string $task, string $idempotencyKey, array $payload, string $createdAt, int $attempt = 0, int $maxAttempts = 5, ?int $timeout = null, $notify = null, ?string $invokerId = null)
    {
        $this->id = $id;
        $this->task = $task;
        $this->idempotencyKey = $idempotencyKey;
        $this->payload = $payload;
        $this->createdAt = $createdAt;
        $this->attempt = $attempt;
        $this->maxAttempts = $maxAttempts;
        $this->timeout = $timeout;
        $this->notify = $notify;
        $this->invokerId = $invokerId;
    }

    public static function create(string $task, array $payload = [], ?string $idempotencyKey = null, ?string $id = null, ?int $maxAttempts = 5, ?int $timeout = null, $notify = null, ?string $invokerId = null): self
    {
        $id = $id ?? \Illuminate\Support\Str::orderedUuid()->toString();
        $idempotencyKey = $idempotencyKey ?? 'gen:'.\Illuminate\Support\Str::orderedUuid()->toString();
        $createdAt = Carbon::now()->toIso8601String();

        return new self(
            id: $id,
            task: $task,
            idempotencyKey: $idempotencyKey,
            payload: $payload,
            createdAt: $createdAt,
            attempt: 0,
            maxAttempts: $maxAttempts ?? 5,
            timeout: $timeout,
            notify: $notify,
            invokerId: $invokerId
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getInvokerId(): ?string
    {
        return $this->invokerId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function toArray(): array
    {
        $arr = [
            'version' => '1.0',
            'id' => $this->id,
            'task' => $this->task,
            'idempotency_key' => $this->idempotencyKey,
            'payload' => $this->payload,
            'created_at' => $this->createdAt,
            'attempt' => $this->attempt,
            'max_attempts' => $this->maxAttempts,
        ];

        if ($this->invokerId !== null) {
            $arr['invoker_id'] = $this->invokerId;
        }

        if ($this->timeout !== null) {
            $arr['timeout_seconds'] = $this->timeout;
        }

        if ($this->notify !== null) {
            $arr['notify'] = $this->notify;
        }

        return $arr;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
