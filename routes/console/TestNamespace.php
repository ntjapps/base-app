<?php

use App\Exceptions\CommonCustomException;
use App\Interfaces\GoQueues;
use App\Mail\TestMail;
use App\Models\User;
use App\Notifications\MessageNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

Artisan::command('test:unit', function () {
    $this->info('Executing test:unit...');

    \Illuminate\Support\Facades\Log::channel('database')->alert('Console test:unit executed', ['appName' => config('app.name')]);

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

Artisan::command('test:log {--enqueue}', function () {
    Log::channel('database')->alert('Console test:log executed', ['appName' => config('app.name')]);
    Log::alert('Console test:log executed', ['appName' => config('app.name')]);

    $this->info('Log test executed');
    $this->info('Log test executed in database channel');

    if ($this->option('enqueue')) {
        $queue = GoQueues::LOGGER;

        $logData = [
            'message' => 'Console test:log executed (enqueued)',
            'channel' => 'console',
            'level' => 550,
            'level_name' => 'ALERT',
            'datetime' => now()->format('Y-m-d H:i:s.u'),
            'context' => ['appName' => config('app.name')],
            'extra' => [],
        ];

        $envelope = [
            'version' => '1.0',
            'id' => (string) \Illuminate\Support\Str::orderedUuid(),
            'task' => 'logger',
            'payload' => $logData,
            'created_at' => now()->toIso8601String(),
            'attempt' => 0,
            'max_attempts' => 5,
        ];

        try {
            $connection = new AMQPStreamConnection(
                config('services.rabbitmq.host'),
                config('services.rabbitmq.port'),
                config('services.rabbitmq.user'),
                config('services.rabbitmq.password'),
                config('services.rabbitmq.vhost')
            );
            $channel = $connection->channel();

            // Declare queue and publish directly
            $channel->queue_declare($queue, false, true, false, false);
            $message = new AMQPMessage(json_encode($envelope), [
                'content_type' => 'application/json',
                'content_encoding' => 'utf-8',
                'delivery_mode' => 2,
            ]);
            $channel->basic_publish($message, '', $queue);

            $channel->close();
            $connection->close();

            $this->info('Enqueued logger task to RabbitMQ');
            $this->line('  queue: '.$queue);
            $this->line('  task_id: '.$envelope['id']);
        } catch (\Throwable $e) {
            $this->error('Enqueue failed: '.$e->getMessage());

            return 3;
        }
    }
})->purpose('Test Log');
