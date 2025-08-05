<?php

namespace App\Observers;

use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WaMessageSentLogObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the WaMessageSentLog "created" event.
     */
    public function created(WaMessageSentLog $waMessageSentLog): void
    {
        if ($waMessageSentLog->success && ! empty($waMessageSentLog->recipient_number)) {
            WaApiMessageThreads::create([
                'phone_number' => $waMessageSentLog->recipient_number,
                'messageable_id' => $waMessageSentLog->id,
                'messageable_type' => get_class($waMessageSentLog),
                'last_message_at' => now(),
            ]);
        }
    }

    /**
     * Handle the WaMessageSentLog "deleted" event.
     */
    public function deleted(WaMessageSentLog $waMessageSentLog): void
    {
        // Delete any associated message thread to prevent orphaned records
        WaApiMessageThreads::where([
            'messageable_id' => $waMessageSentLog->id,
            'messageable_type' => get_class($waMessageSentLog),
        ])->delete();
    }
}
