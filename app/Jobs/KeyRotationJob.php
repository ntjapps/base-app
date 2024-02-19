<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
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
        return 3;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'KeyRotationJob';
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
        return ['KeyRotationJob', 'uniqueId: '.$this->uniqueId()];
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

            $newKey = config('app.key');
            if (Str::startsWith($newKey, 'base64:')) {
                $newKey = base64_decode(Str::after($newKey, 'base64:'));
            }

            $decrypterInstance = new Encrypter($oldKey, config('app.cipher'));
            $encrypterInstance = new Encrypter($newKey, config('app.cipher'));

            try {
                DB::transaction(function () use ($decrypterInstance, $encrypterInstance) {
                    /** Update Encrypted Data with New Key must use DB Facade not Eloquent to prevent call to CAST */
                    DB::table('users')->chunkById(1000, function (Collection $users) use ($decrypterInstance, $encrypterInstance) {
                        foreach ($users as $user) {

                            (array) $updatedData = [];
                            if (! is_null($user->totp_key)) {
                                $updatedData['totp_key'] = $encrypterInstance->encrypt($decrypterInstance->decrypt($user->totp_key, false), false);
                            }

                            DB::table('users')->where('id', $user->id)->update($updatedData);
                        }
                    });
                });
            } catch (DecryptException $e) {
                Log::debug('Job Error', ['jobName' => 'KeyRotationJob', 'message' => 'Error decrypting data with old key.']);
                throw $e;
            }

            Log::debug('Job Finished', ['jobName' => 'KeyRotationJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'KeyRotationJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
