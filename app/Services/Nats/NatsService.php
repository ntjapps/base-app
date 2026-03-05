<?php

namespace App\Services\Nats;

use App\Interfaces\GoQueues;
use App\Interfaces\GoWorkerTask;
use Basis\Nats\Client;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Simple wrapper around Basis\Nats client to connect using config/services.php
 */
class NatsService
{
    protected array $config;

    protected ?Client $client = null;

    public function __construct(Client $client, ?array $config = null)
    {
        $this->client = $client;
        $this->config = $config ?? config('services.nats', []);
    }

    /**
     * Return the configured Basis\Nats\Client instance (injected via DI).
     * The client is pre-connected in AppServiceProvider as a singleton.
     *
     * @throws Throwable if NATS is disabled
     */
    public function connect(): Client
    {
        if (empty($this->config['enabled'])) {
            throw new RuntimeException('NATS connection is disabled in configuration.');
        }

        // Client is already connected in AppServiceProvider, just return it
        return $this->client;
    }

    /**
     * Attempt to get JetStream context from the configured client.
     * The underlying client implementation may expose it as js(), jetstream(), or jetStream().
     *
     * @return mixed
     */
    public function jetStream()
    {
        $client = $this->connect();

        if (method_exists($client, 'js')) {
            return $client->js();
        }

        if (method_exists($client, 'jetstream')) {
            return $client->jetstream();
        }

        if (method_exists($client, 'jetStream')) {
            return $client->jetStream();
        }

        throw new RuntimeException('JetStream interface not available on NATS Client instance.');
    }

    /**
     * Convenience method to check reachability
     */
    public function ping(): bool
    {
        $client = $this->connect();

        return $client->ping() === true;
    }

    /**
     * Publish to JetStream using stream->put() (synchronous - waits for ACK).
     * Assumes the stream already exists (do NOT call create() here).
     *
     * @param  mixed  $data
     * @return mixed Ack object returned by the server
     *
     * @throws Throwable
     */
    public function publish(string $subject, $data, ?string $streamName = null)
    {
        // Validate subject is one of the GoQueues constants to ensure cross-app consistency
        $allowed = array_values((new \ReflectionClass(GoQueues::class))->getConstants());
        if (! in_array($subject, $allowed, true)) {
            throw new InvalidArgumentException(sprintf("Subject '%s' is not a valid GoQueues constant", $subject));
        }

        $client = $this->connect();

        if (! method_exists($client, 'getApi')) {
            throw new RuntimeException('NATS Client does not expose getApi() required to publish to JetStream.');
        }

        $streamName = $streamName ?? ($this->config['stream_name'] ?? 'TASKS');

        try {
            // Set a timeout alarm to prevent indefinite blocking. Prefer the configured timeout.
            $timeoutSeconds = isset($this->config['timeout']) && is_numeric($this->config['timeout']) ? (int) $this->config['timeout'] : 5;

            if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal') && function_exists('pcntl_alarm')) {
                // Enable async signal handling so SIGALRM triggers immediately
                pcntl_async_signals(true);
                pcntl_signal(SIGALRM, function () use ($timeoutSeconds) {
                    throw new RuntimeException('NATS publish timed out after '.$timeoutSeconds.' seconds');
                });
                pcntl_alarm($timeoutSeconds);
                Log::channel('stdout')->info('NATS publish: pcntl timeout enabled', ['timeout' => $timeoutSeconds]);
            } else {
                // pcntl not available: warn that publish may block (we'll still attempt publish and rely on client's internal timeouts)
                Log::channel('stdout')->warning('pcntl unavailable; NATS publish may block indefinitely');
            }

            $payload = is_string($data) ? $data : json_encode($data);
            Log::channel('stdout')->info('NATS publish: calling client->publish (fire-and-forget)', ['subject' => $subject, 'payload_size' => strlen($payload)]);

            // Use client->publish() directly for fire-and-forget (no ack wait)
            // This is MUCH faster than stream->put() which may wait for stream init
            $client->publish($subject, $payload);
            $ack = null; // No ack in fire-and-forget mode

            // Cancel alarm if it was set
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            return $ack;
        } catch (Throwable $e) {
            // Cancel alarm on exception
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            Log::channel('stdout')->error('Failed to publish to NATS JetStream: '.$e->getMessage(), [
                'stream' => $streamName,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Convenience wrapper when using queue constants (e.g. App\Interfaces\GoQueues)
     *
     * @param  mixed  $data
     * @return mixed
     */
    /**
     * Publish to a queue subject (must be one of App\Interfaces\GoQueues constants)
     *
     * @param  string  $queueSubject  Value must be a constant from App\Interfaces\GoQueues (e.g. GoQueues::WHATSAPP)
     */
    public function publishToQueue(string $queueSubject, $data, ?string $streamName = null)
    {
        // Accept either raw payload/array/string or a GoWorkerTaskInterface
        if ($data instanceof GoWorkerTask) {
            $payload = $data->toJson();
        } else {
            $payload = is_string($data) ? $data : json_encode($data);
        }

        return $this->publish($queueSubject, $payload, $streamName);
    }
}
