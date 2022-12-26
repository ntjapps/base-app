<?php

namespace App\Logger;

use App\Traits\TelegramApi;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class TelegramHandler extends AbstractProcessingHandler
{
    use TelegramApi;

    protected function write(LogRecord $record): void
    {
        (string) $message = $record['level_name'].': '.$record['message'];

        $this->sendTelegramMessage($message);

        (string) $context = 'Context: '.json_encode($record['context']);

        $this->sendTelegramMessage($context);
    }
}
