<?php

namespace App\Jobs;

use App\Logger\Models\ServerLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
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
        // $this->onQueue('default');
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
        return 3;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'PruneLogDebugLevelJob';
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
        return ['PruneLogDebugLevelJob', 'uniqueId: '.$this->uniqueId()];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'PruneLogDebugLevelJob']);

            /** Memory Leak mitigation */
            if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::stopRecording();
            }

            /** Delete Server Logs */
            ServerLog::where('level', Logger::toMonologLevel('debug'))->where('created_at', '<=', now()->subWeek())->delete();
            ServerLog::where('level', Logger::toMonologLevel('info'))->where('created_at', '<=', now()->subWeeks(2))->delete();
            ServerLog::where('level', Logger::toMonologLevel('notice'))->where('created_at', '<=', now()->subWeeks(3))->delete();
            ServerLog::where('level', Logger::toMonologLevel('warning'))->where('created_at', '<=', now()->subWeeks(4))->delete();

            /** Delete Notifications */
            DB::beginTransaction();
            try {
                DB::table('notifications')->where('created_at', '<=', now()->subWeek())->delete();
                DB::table('notifications')->whereNotNull('read_at')->where('read_at', '<=', now()->subDay())->delete();
                DB::commit();
            } catch (QueryException $e) {
                DB::rollBack();
                Log::warning('Notification Prune Failed', ['jobName' => 'PruneLogDebugLevelJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            }

            /** Delete Celery Tables */
            DB::beginTransaction();
            try {
                DB::table('celery_taskmeta')->where('date_done', '<=', now()->subDays(3))->delete();
                DB::table('celery_tasksetmeta')->where('date_done', '<=', now()->subDays(3))->delete();
                DB::commit();
            } catch (QueryException $e) {
                DB::rollBack();
                Log::warning('Celery Prune Failed', ['jobName' => 'PruneLogDebugLevelJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            }

            /** Memory Leak mitigation */
            if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }

            Log::debug('Job Finished', ['jobName' => 'PruneLogDebugLevelJob']);
        } catch (\Throwable $e) {
            /** Memory Leak mitigation */
            if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }

            Log::error('Job Failed', ['jobName' => 'PruneLogDebugLevelJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
