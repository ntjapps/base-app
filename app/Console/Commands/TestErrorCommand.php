<?php

namespace App\Console\Commands;

use App\Exceptions\CommonCustomException;
use Illuminate\Console\Command;

class TestErrorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:error';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Common Custom Exception.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        throw new CommonCustomException('Test Error');
    }
}
