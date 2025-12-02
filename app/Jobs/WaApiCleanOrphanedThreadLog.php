<?php

namespace App\Jobs;

use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaApiCleanOrphanedThreadLog implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
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
        return 'WaApiCleanOrphanedThreadLog';
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
        return ['WaApiCleanOrphanedThreadLog', 'uniqueId: '.$this->uniqueId()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'WaApiCleanOrphanedThreadLog']);

            

            // Check if WhatsApp service is enabled
            if (! Config::get('services.whatsapp.enabled', false)) {
                Log::info('WhatsApp service is disabled. Skipping orphaned thread cleanup.');

                return;
            }

            // Get all threads
            WaApiMessageThreads::chunkById(100, function ($threads) {
                foreach ($threads as $thread) {
                    $messageable_id = $thread->messageable_id;
                    $messageable_type = $thread->messageable_type;

                    // Check if the referenced message still exists
                    $exists = false;

                    if ($messageable_type === WaMessageSentLog::class) {
                        $exists = WaMessageSentLog::where('id', $messageable_id)->exists();
                    } elseif ($messageable_type === WaMessageWebhookLog::class) {
                        $exists = WaMessageWebhookLog::where('id', $messageable_id)->exists();
                    }

                    // If the referenced message doesn't exist, delete the thread
                    if (! $exists) {
                        Log::info('Deleting orphaned thread', [
                            'thread_id' => $thread->id,
                            'messageable_id' => $messageable_id,
                            'messageable_type' => $messageable_type,
                        ]);
                        $thread->delete();
                    }
                }
            });

            // Check for threads that refer to non-existent models or classes
            $invalidThreads = DB::table('wa_api_message_threads')
                ->whereNotIn('messageable_type', [
                    WaMessageSentLog::class,
                    WaMessageWebhookLog::class,
                ])
                ->orWhereNull('messageable_id')
                ->orWhereNull('messageable_type')
                ->delete();

            if ($invalidThreads > 0) {
                Log::info("Deleted {$invalidThreads} threads with invalid messageable types or IDs");
            }

            

            Log::debug('Job Finished', ['jobName' => 'WaApiCleanOrphanedThreadLog']);
        } catch (\Throwable $e) {
            

            Log::error('Job Failed', ['jobName' => 'WaApiCleanOrphanedThreadLog', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
