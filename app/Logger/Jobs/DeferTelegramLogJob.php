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
use Monolog\LogRecord;

class DeferTelegramLogJob implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, TelegramApi;

    /**
     * Create a new job instance.
     */
    public function __construct(public LogRecord $record)
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

        (string) $message = $this->record['level_name'].': '.$this->record['message'];
        $this->sendTelegramMessage($message);
        (string) $context = 'Context: '.json_encode($this->record['context']);
        $this->sendTelegramMessage($context);
    }
}
