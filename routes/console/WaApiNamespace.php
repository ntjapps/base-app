<?php

use App\Interfaces\WaApiMetaInterfaceClass;
use App\Jobs\WhatsApp\SendMessageJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('whatsapp:reply {phone_number} {message}', function () {
    $phoneNumber = $this->argument('phone_number');
    $message = $this->argument('message');
    $waApi = new WaApiMetaInterfaceClass;

    // Check if WhatsApp service is enabled
    if (! config('services.whatsapp.enabled')) {
        $this->error('WhatsApp service is disabled. Message not sent.');
        Log::warning('Attempted to use WhatsApp API while disabled', ['command' => 'whatsapp:reply']);

        return 1;
    }

    // Validate phone number format (should be numbers only, no + or spaces)
    if (! preg_match('/^\d+$/', $phoneNumber)) {
        $this->error('Invalid phone number format. Use numbers only without + or spaces.');

        return 1;
    }

    // Check if we can reply to this number (has recent message)
    if (! $waApi->hasRecentMessage($phoneNumber)) {
        $this->error('Cannot reply to this number. No messages received within the last 24 hours.');
        $this->info('Try using whatsapp:send instead for new conversations.');

        return 1;
    }

    // Queue the message to be sent asynchronously via SendMessageJob
    SendMessageJob::dispatch($phoneNumber, $message, false);
    $this->info("Message queued for sending to {$phoneNumber}");
    Log::info('WhatsApp reply queued via command', [
        'phone_number' => $phoneNumber,
    ]);

    return 0;
})->purpose('Reply to a WhatsApp number (must have received a message from this number within the last 24 hours)');

Artisan::command('whatsapp:send {phone_number} {message} {--template=} {--preview=}', function () {
    $phoneNumber = $this->argument('phone_number');
    $message = $this->argument('message');
    $template = $this->option('template');
    $preview = $this->option('preview');
    $waApi = new WaApiMetaInterfaceClass;

    // Check if WhatsApp service is enabled
    if (! config('services.whatsapp.enabled')) {
        $this->error('WhatsApp service is disabled. Message not sent.');
        Log::warning('Attempted to use WhatsApp API while disabled', ['command' => 'whatsapp:send']);

        return 1;
    }

    // Validate phone number format (should be numbers only, no + or spaces)
    if (! preg_match('/^\d+$/', $phoneNumber)) {
        $this->error('Invalid phone number format. Use numbers only without + or spaces.');

        return 1;
    }

    $previewUrl = $preview ? true : false;

    // Show warning about 24-hour window for non-template messages
    if (! $template) {
        $this->warn('Warning: You are sending a non-template message.');
        $this->warn('This will only work if you have received a message from this number in the last 24 hours.');

        if (! $waApi->hasRecentMessage($phoneNumber) && ! $this->confirm('No recent messages from this number. Continue anyway?')) {
            $this->info('Message sending cancelled.');

            return 0;
        }
    }

    // Queue the message to be sent asynchronously via SendMessageJob
    SendMessageJob::dispatch($phoneNumber, $message, $previewUrl);
    $this->info("Message queued for sending to {$phoneNumber}");
    Log::info('WhatsApp message queued via command', [
        'phone_number' => $phoneNumber,
        'template' => $template ?: 'none',
        'preview_url' => $previewUrl,
    ]);

    return 0;
})->purpose('Send a WhatsApp message (warning: may require pre-approved templates for new conversations)');

Artisan::command('whatsapp:test {phone_number}', function () {
    $phoneNumber = $this->argument('phone_number');

    // Check if WhatsApp service is enabled
    if (! config('services.whatsapp.enabled')) {
        $this->error('WhatsApp service is disabled. Message not sent.');
        Log::warning('Attempted to use WhatsApp API while disabled', ['command' => 'whatsapp:test']);

        return 1;
    }

    // Validate phone number format (should be numbers only, no + or spaces)
    if (! preg_match('/^\d+$/', $phoneNumber)) {
        $this->error('Invalid phone number format. Use numbers only without + or spaces.');

        return 1;
    }

    $this->info('Dispatching BookingReminderJob...');
    dispatch(new BookingReminderJob($phoneNumber));

    $this->info('Job dispatched successfully. Check the logs for details.');

    return 0;
})->purpose('Test WhatsApp API by dispatching a BookingReminderJob with the given phone number');
