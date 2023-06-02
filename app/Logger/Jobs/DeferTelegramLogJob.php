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
        //
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
