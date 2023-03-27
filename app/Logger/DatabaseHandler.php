<?php

namespace App\Logger;

use App\Logger\Jobs\DeferDatabaseLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        DeferDatabaseLogJob::dispatch($record);
    }
}
