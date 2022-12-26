<?php

namespace App\Jobs;

use App\Logger\Models\ServerLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;

class PruneLogDebugLevelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('Job Executed', ['jobName' => 'PruneLogDebugLevelJob']);
        ServerLog::where('level', Logger::toMonologLevel('debug'))->where('created_at', '<=', now()->subWeek())->delete();
        ServerLog::where('level', Logger::toMonologLevel('info'))->where('created_at', '<=', now()->subWeeks(2))->delete();
        ServerLog::where('level', Logger::toMonologLevel('notice'))->where('created_at', '<=', now()->subWeeks(3))->delete();
        ServerLog::where('level', Logger::toMonologLevel('warning'))->where('created_at', '<=', now()->subWeeks(4))->delete();
        Log::debug('Job Finished', ['jobName' => 'PruneLogDebugLevelJob']);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('Job Failed', ['jobName' => 'PruneLogDebugLevelJob', 'error' => $e->getMessage()]);
    }
}
