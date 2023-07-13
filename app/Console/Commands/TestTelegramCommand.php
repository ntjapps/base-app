<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestTelegramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram Bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
    }
}
