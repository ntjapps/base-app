<?php

namespace App\Logger;

use App\Logger\Jobs\DeferTelegramLogJob;
use Illuminate\Support\Facades\Bus;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class TelegramHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        (string) $message = $record['level_name'].': '.$record['message'];
        (string) $context = 'Context: '.json_encode($record['context']);

        Bus::chain([
            new DeferTelegramLogJob($message, config('telegram.group_id')),
            new DeferTelegramLogJob($context, config('telegram.group_id')),
        ])->dispatch();
    }
}
