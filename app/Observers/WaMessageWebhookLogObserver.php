<?php

namespace App\Observers;

use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Carbon\Carbon;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WaMessageWebhookLogObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the WaMessageWebhookLog "created" event.
     */
    public function created(WaMessageWebhookLog $waMessageWebhookLog): void
    {
        if (! empty($waMessageWebhookLog->message_from)) {
            WaApiMessageThreads::create([
                'phone_number' => $waMessageWebhookLog->message_from,
                'messageable_id' => $waMessageWebhookLog->id,
                'messageable_type' => get_class($waMessageWebhookLog),
                'last_message_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Handle the WaMessageWebhookLog "deleted" event.
     */
    public function deleted(WaMessageWebhookLog $waMessageWebhookLog): void
    {
        // Delete any associated message thread to prevent orphaned records
        WaApiMessageThreads::where([
            'messageable_id' => $waMessageWebhookLog->id,
            'messageable_type' => get_class($waMessageWebhookLog),
        ])->delete();
    }
}
