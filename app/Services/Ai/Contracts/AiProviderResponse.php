<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

class AiProviderResponse
{
    public function __construct(
        public readonly string $text,
        public readonly bool $success,
        public readonly ?array $toolCalls = null,
        public readonly ?array $metadata = null,
        public readonly ?string $error = null,
    ) {}

    /**
     * Create a successful response.
     */
    public static function success(
        string $text,
        ?array $toolCalls = null,
        ?array $metadata = null
    ): self {
        return new self(
            text: $text,
            success: true,
            toolCalls: $toolCalls,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed response.
     */
    public static function failure(string $error, ?array $metadata = null): self
    {
        return new self(
            text: '',
            success: false,
            metadata: $metadata,
            error: $error,
        );
    }

    /**
     * Check if response contains tool calls.
     */
    public function hasToolCalls(): bool
    {
        return ! empty($this->toolCalls);
    }

    /**
     * Get metadata value by key.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
