<?php

namespace App\Logger;

use App\Logger\Jobs\DeferTelegramLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class TelegramHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        DeferTelegramLogJob::dispatch($record, config('telegram.group_id'));
    }
}
