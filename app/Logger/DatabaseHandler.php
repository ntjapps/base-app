<?php

namespace App\Logger;

use App\Logger\Jobs\DeferDatabaseLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            DeferDatabaseLogJob::dispatch($record);
        } catch (\Throwable $e) {
            syslog(LOG_ERR, 'DatabaseHandler dispatch failed: '.json_encode([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }
    }
}
