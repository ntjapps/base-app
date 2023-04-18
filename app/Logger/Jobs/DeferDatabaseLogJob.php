<?php

namespace App\Logger\Jobs;

use App\Logger\Models\ServerLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Laravel\Horizon\Contracts\Silenced;
use Laravel\Telescope\Telescope;
use Monolog\LogRecord;

class DeferDatabaseLogJob implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable;

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
}
