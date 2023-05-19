<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KeyRotationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'KeyRotationJob']);

            $oldKey = config('keyrotate.old_key');
            if (Str::startsWith($oldKey, 'base64:')) {
                $oldKey = base64_decode(Str::after($oldKey, 'base64:'));
            }

            $decrypterInstance = new Encrypter($oldKey, config('app.cipher'));

            try {
                DB::transaction(function () {
                    // Update encrypted data with new key
                });
            } catch (DecryptException $e) {
                Log::debug('Job Error', ['jobName' => 'KeyRotationJob', 'message' => 'Error decrypting data with old key.']);
                throw $e;
            }

            Log::debug('Job Finished', ['jobName' => 'KeyRotationJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'KeyRotationJob', 'error' => $e->getMessage(), 'previous' => $e->getPrevious()]);
            throw $e;
        }
    }
}
