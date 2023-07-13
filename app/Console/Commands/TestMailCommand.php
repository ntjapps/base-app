<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:mail {send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mail sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Mail::mailer('smtp')->to($this->argument('send'))->send(new TestMail);
        $this->info('Mail sent to '.$this->argument('send'));

        Log::alert('Console mail:test executed', ['send' => $this->argument('send')]);
    }
}
