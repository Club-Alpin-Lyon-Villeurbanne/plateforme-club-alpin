<?php

namespace App\Bridge\Monolog\Processor;

class SAPIProcessor
{
    public function __invoke(array $record)
    {
        $record['extra']['SAPI'] = \PHP_SAPI;

        // cli_get_process_title triggers warnings on some platforms
        if (\function_exists('cli_get_process_title') && $title = @cli_get_process_title()) {
            $record['extra']['process_title'] = $title;
        }

        return $record;
    }
}
