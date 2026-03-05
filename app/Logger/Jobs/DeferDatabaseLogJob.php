<?php

namespace App\Logger\Jobs;

use App\Interfaces\GoQueues;
use App\Logger\Models\ServerLog;
use App\Traits\GoWorkerFunction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Monolog\LogRecord;

class DeferDatabaseLogJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public LogRecord $record)
    {
        $this->onQueue('logger');
    }

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    // public $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 1;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'DeferDatabaseLogJob';
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 60;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['DeferDatabaseLogJob', 'uniqueId: '.$this->uniqueId()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** Memory Leak mitigation: Telescope removed — no-op placeholder. */

        // Determine if any queue backend is enabled (prefer NATS)
        $natsEnabled = config('services.nats.enabled', true);
        $rabbitEnabled = config('services.rabbitmq.enabled', true);
        $useQueue = $natsEnabled || $rabbitEnabled;

        // Debug: Log the decision - send to stdout channel to avoid feedback loop
        try {
            \Illuminate\Support\Facades\Log::channel('stdout')->info('useQueue: '.($useQueue ? 'YES' : 'NO').', NATS: '.($natsEnabled ? 'YES' : 'NO').', RabbitMQ: '.($rabbitEnabled ? 'YES' : 'NO'));
        } catch (\Exception $ignored) {
        }

        if ($useQueue) {
            // Prefer NATS worker backend when NATS is enabled, otherwise fall back to RabbitMQ
            $preferred = $natsEnabled ? 'nats' : 'rabbitmq';
            $workerBackend = config("services.{$preferred}.worker_backend", config('services.rabbitmq.worker_backend', 'go'));

            // Debug: Log worker backend and preferred transport to stdout channel
            try {
                \Illuminate\Support\Facades\Log::channel('stdout')->info('workerBackend: '.$workerBackend.' (preferred: '.$preferred.')');
            } catch (\Exception $ignored) {
            }

            $logData = [
                'message' => $this->record['message'],
                'channel' => $this->record['channel'],
                'level' => $this->record['level'],
                'level_name' => $this->record['level_name'],
                'datetime' => $this->record['datetime']->format('Y-m-d H:i:s.u'),
                'context' => $this->record['context'],
                'extra' => $this->record['extra'],
            ];

            switch ($workerBackend) {
                case 'celery':
                    // Send to Celery worker using legacy format (fallback option)
                    $this->sendTask('log_db_task', [json_encode($logData)], 'logger');
                    break;
                case 'both':
                    // Send to both backends for migration scenarios
                    $this->sendGoTask('logger', $logData, GoQueues::LOGGER);
                    $this->sendTask('log_db_task', [json_encode($logData)], 'logger');
                    break;
                case 'go':
                default:
                    // Send to Go worker using modern format (default)
                    try {
                        \Illuminate\Support\Facades\Log::channel('stdout')->info('About to call sendGoTask');
                    } catch (\Exception $ignored) {
                    }

                    $this->sendGoTask('logger', $logData, GoQueues::LOGGER);

                    try {
                        \Illuminate\Support\Facades\Log::channel('stdout')->info('sendGoTask completed');
                    } catch (\Exception $ignored) {
                    }
                    break;
            }
        } else {
            // Synchronous database write when no queue backend is available
            ServerLog::create([
                'message' => $this->record['message'],
                'channel' => $this->record['channel'],
                'level' => $this->record['level'],
                'level_name' => $this->record['level_name'],
                'datetime' => $this->record['datetime'],
                'context' => $this->record['context'],
                'extra' => $this->record['extra'],
            ]);
        }

        /** Memory Leak mitigation: Telescope removed — no-op placeholder. */
    }
}
