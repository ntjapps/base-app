<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PassportInitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create clients from .env
        Artisan::call('passport:client:env');
        Artisan::call('passport:client:grant:env');
        Artisan::call('passport:client:rabbitmq:env');

        // Provide summary info (no secrets printed) by invoking the helper console command
        Artisan::call('passport:client:rabbitmq:info');
        if ($this->command) {
            $this->command->info(Artisan::output());
        }
    }
}
