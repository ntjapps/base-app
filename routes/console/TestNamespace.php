<?php

use App\Exceptions\CommonCustomException;
use App\Mail\TestMail;
use App\Models\User;
use App\Notifications\MessageNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

Artisan::command('test:unit', function () {
    /** NULL */
    $this->info('Executing test:unit...');
    Log::alert('Console test:unit executed', ['appName' => config('app.name')]);
})->purpose('Test Query / Any Test / Sample test for unit testing');

Artisan::command('test:error', function () {
    throw new CommonCustomException('Test Error');
})->purpose('Test Common Custom Exception');

Artisan::command('test:mail {send}', function () {
    Mail::mailer('smtp')->to($this->argument('send'))->send(new TestMail);
    $this->info('Mail sent to '.$this->argument('send'));

    Log::alert('Console mail:test executed', ['send' => $this->argument('send')]);
})->purpose('Test mail sending');

Artisan::command('test:telegram', function () {
    $auth = Http::asForm()->post(config('telegram.endpoint').config('telegram.token').'/getMe');

    if ($auth->successful()) {
        $this->info('Telegram Bot is connected');

        $message = 'Test Telegram Bot';

        $response = Http::asForm()->post(config('telegram.endpoint').config('telegram.token').'/sendMessage', [
            'chat_id' => config('telegram.group_id'),
            'text' => substr($message, 0, 4096),
        ]);

        if ($response->successful()) {
            $this->info('Telegram Bot sent message');
        } else {
            $this->info('Telegram Bot failed to send message');
        }
    } else {
        $this->info('Telegram Bot is not connected');
    }

    Log::alert('Console test:telegram executed', ['appName' => config('app.name')]);
})->purpose('Test Telegram Bot');

Artisan::command('test:notification {username}', function () {
    $user = User::where('username', $this->argument('username'))->first();

    if ($user) {
        $user->notify(new MessageNotification('Test Notification', 'This is a test notification'));
        $this->info('Notification sent to '.$this->argument('username'));
    } else {
        $this->info('User not found');
    }

    Log::alert('Console test:notification executed', ['username' => $this->argument('username')]);
})->purpose('Test Notification');
