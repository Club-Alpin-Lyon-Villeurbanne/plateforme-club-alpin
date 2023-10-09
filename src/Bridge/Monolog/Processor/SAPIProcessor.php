<?php

namespace App\Bridge\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class SAPIProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record)
    {
        $record['extra']['SAPI'] = \PHP_SAPI;

        // cli_get_process_title triggers warnings on some platforms
        if (\function_exists('cli_get_process_title') && $title = @cli_get_process_title()) {
            $record['extra']['process_title'] = $title;
        }

        return $record;
    }
}
