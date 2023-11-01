<?php

namespace App\Logger;

use App\Logger\Jobs\DeferDatabaseLogJob;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            DB::connection()->getPdo();

            DeferDatabaseLogJob::dispatch($record);
        } catch (\Throwable $e) {
            return;
        }

    }
}
