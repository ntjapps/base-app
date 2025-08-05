<?php

use App\Interfaces\WaApiMetaInterfaceClass;
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

    // Send the message
    $result = $waApi->sendMessage($phoneNumber, $message);

    if ($result) {
        $messageId = $result['messages'][0]['id'] ?? 'unknown';
        $this->info("Message sent successfully to {$phoneNumber}");
        $this->info("Message ID: {$messageId}");
        Log::info('WhatsApp reply sent via command', [
            'phone_number' => $phoneNumber,
            'message_id' => $messageId,
        ]);

        return 0;
    } else {
        $this->error("Failed to send message to {$phoneNumber}");
        $this->info('Check logs for more details.');

        return 1;
    }
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

    // Send the message
    $result = $waApi->sendMessage($phoneNumber, $message, $previewUrl);

    if ($result) {
        $messageId = $result['messages'][0]['id'] ?? 'unknown';
        $this->info("Message sent successfully to {$phoneNumber}");
        $this->info("Message ID: {$messageId}");
        Log::info('WhatsApp message sent via command', [
            'phone_number' => $phoneNumber,
            'message_id' => $messageId,
            'template' => $template ?: 'none',
            'preview_url' => $previewUrl,
        ]);

        return 0;
    } else {
        $this->error("Failed to send message to {$phoneNumber}");
        $this->info('Check logs for more details.');

        return 1;
    }
})->purpose('Send a WhatsApp message (warning: may require pre-approved templates for new conversations)');
