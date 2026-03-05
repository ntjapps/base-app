<?php

namespace Tests\Unit;

use App\Traits\GoWorkerFunction;
use PHPUnit\Framework\TestCase;

class GoWorkerFunctionTest extends TestCase
{
    use GoWorkerFunction;

    /**
     * Test idempotency key generation with same payload produces same key
     */
    public function test_idempotency_key_generation_is_deterministic(): void
    {
        $payload1 = ['user_id' => '123', 'action' => 'create', 'data' => ['name' => 'Test']];
        $payload2 = ['user_id' => '123', 'action' => 'create', 'data' => ['name' => 'Test']];

        $normalized1 = $this->normalizeForIdempotency($payload1);
        $normalized2 = $this->normalizeForIdempotency($payload2);

        $key1 = hash('sha256', 'test_task|'.$normalized1);
        $key2 = hash('sha256', 'test_task|'.$normalized2);

        $this->assertEquals($key1, $key2, 'Idempotency keys should be identical for same payload');
    }

    /**
     * Test idempotency key generation with different payload order produces same key
     */
    public function test_idempotency_key_ignores_payload_order(): void
    {
        $payload1 = ['user_id' => '123', 'action' => 'create', 'name' => 'Test'];
        $payload2 = ['name' => 'Test', 'user_id' => '123', 'action' => 'create'];

        $normalized1 = $this->normalizeForIdempotency($payload1);
        $normalized2 = $this->normalizeForIdempotency($payload2);

        $this->assertEquals($normalized1, $normalized2, 'Normalized payloads should be identical regardless of key order');
    }

    /**
     * Test idempotency key generation with different payloads produces different keys
     */
    public function test_idempotency_key_differs_for_different_payloads(): void
    {
        $payload1 = ['user_id' => '123', 'action' => 'create'];
        $payload2 = ['user_id' => '456', 'action' => 'create'];

        $normalized1 = $this->normalizeForIdempotency($payload1);
        $normalized2 = $this->normalizeForIdempotency($payload2);

        $key1 = hash('sha256', 'test_task|'.$normalized1);
        $key2 = hash('sha256', 'test_task|'.$normalized2);

        $this->assertNotEquals($key1, $key2, 'Idempotency keys should differ for different payloads');
    }

    /**
     * Test recursive key sorting with nested arrays
     */
    public function test_recursive_key_sort_handles_nested_arrays(): void
    {
        $payload = [
            'z' => 'last',
            'a' => 'first',
            'nested' => [
                'z' => 'nested_last',
                'a' => 'nested_first',
            ],
        ];

        $sorted = $this->recursiveKeySort($payload);

        $keys = array_keys($sorted);
        $this->assertEquals(['a', 'nested', 'z'], $keys, 'Top-level keys should be sorted');

        $nestedKeys = array_keys($sorted['nested']);
        $this->assertEquals(['a', 'z'], $nestedKeys, 'Nested keys should be sorted');
    }

    /**
     * Test normalization with complex payload
     */
    public function test_normalization_handles_complex_payload(): void
    {
        $payload = [
            'user_id' => '123',
            'metadata' => [
                'tags' => ['beta', 'alpha'],
                'settings' => ['key1' => 'value1', 'key2' => 'value2'],
            ],
            'timestamp' => '2026-01-04T12:00:00Z',
        ];

        $normalized = $this->normalizeForIdempotency($payload);

        $this->assertIsString($normalized, 'Normalized output should be a JSON string');
        $this->assertJson($normalized, 'Normalized output should be valid JSON');

        // Verify it can be decoded
        $decoded = json_decode($normalized, true);
        $this->assertIsArray($decoded, 'Normalized JSON should decode to array');
    }

    /**
     * Test normalization preserves list arrays (non-associative)
     */
    public function test_normalization_preserves_list_arrays(): void
    {
        $payload = [
            'items' => [1, 2, 3],
            'tags' => ['a', 'b', 'c'],
        ];

        $normalized = $this->normalizeForIdempotency($payload);
        $decoded = json_decode($normalized, true);

        $this->assertEquals([1, 2, 3], $decoded['items'], 'List arrays should preserve order');
        $this->assertEquals(['a', 'b', 'c'], $decoded['tags'], 'List arrays should preserve order');
    }

    public function test_normalization_sorts_objects_by_keys(): void
    {
        $o = new \stdClass;
        $o->z = 'last';
        $o->a = 'first';

        $normalized = $this->normalizeForIdempotency(['obj' => $o]);
        $decoded = json_decode($normalized, true);

        $this->assertIsArray($decoded['obj']);
        $this->assertEquals(['a', 'z'], array_keys($decoded['obj']));
        $this->assertEquals('first', $decoded['obj']['a']);
        $this->assertEquals('last', $decoded['obj']['z']);
    }
}
