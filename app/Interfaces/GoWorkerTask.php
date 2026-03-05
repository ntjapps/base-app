<?php

namespace App\Interfaces;

interface GoWorkerTask
{
    public function getId(): string;

    public function getTask(): string;

    public function getIdempotencyKey(): string;

    public function getPayload(): array;

    public function getInvokerId(): ?string;

    public function getCreatedAt(): string;

    public function getAttempt(): int;

    public function getMaxAttempts(): int;

    public function toArray(): array;

    public function toJson(): string;
}
