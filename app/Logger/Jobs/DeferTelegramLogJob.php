<?php

namespace App\Logger\Jobs;

use App\Traits\TelegramApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Laravel\Horizon\Contracts\Silenced;
use Laravel\Telescope\Telescope;

class DeferTelegramLogJob implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TelegramApi;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $data, public string $chatId)
    {
        //$this->onQueue('default');
    }

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    //public $timeout = 60;

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
        return 'DeferTelegramLogJob';
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
        return ['DeferTelegramLogJob', 'uniqueId: ' . $this->uniqueId()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** Memory Leak mitigation */
        if (App::environment('local')) {
            Telescope::stopRecording();
        }

        $this->sendTelegramMessage($this->data, $this->chatId);
    }
}
