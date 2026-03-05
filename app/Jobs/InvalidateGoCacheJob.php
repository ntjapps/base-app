<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvalidateGoCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $type;

    public ?string $key;

    /**
     * Create a new job instance.
     *
     * @param  string  $type  The type of cache to invalidate (e.g., 'instruction', 'division')
     * @param  string|null  $key  The specific key to invalidate (optional, for 'instruction')
     */
    public function __construct(string $type, ?string $key = null)
    {
        $this->type = $type;
        $this->key = $key;
        $this->onQueue('cache.invalidation');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'InvalidateGoCacheJob';
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
        return ['InvalidateGoCacheJob', 'uniqueId: '.$this->uniqueId()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'InvalidateGoCacheJob']);

            // Do something here

            Log::debug('Job Finished', ['jobName' => 'InvalidateGoCacheJob']);
        } catch (\Throwable $e) {
            // (Telescope removed) ensure job cleanup here if needed

            Log::error('Job Failed', ['jobName' => 'InvalidateGoCacheJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
