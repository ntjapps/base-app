<?php

namespace App\Logger\Jobs;

use App\Logger\Models\ServerLog;
use App\Traits\CeleryFunction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Monolog\LogRecord;

class DeferDatabaseLogJob implements ShouldQueue
{
    use CeleryFunction, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        /** Memory Leak mitigation */
        if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        if (config('services.rabbitmq.enabled')) {
            $this->sendTask('log_db_task', [json_encode([
                'message' => $this->record['message'],
                'channel' => $this->record['channel'],
                'level' => $this->record['level'],
                'level_name' => $this->record['level_name'],
                'datetime' => $this->record['datetime'],
                'context' => $this->record['context'],
                'extra' => $this->record['extra'],
            ])], 'logger');
        } else {
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

        /** Memory Leak mitigation */
        if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::startRecording();
        }
    }
}
