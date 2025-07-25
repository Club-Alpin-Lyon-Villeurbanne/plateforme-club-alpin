<?php

namespace App\Bridge\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class ServerLoadProcessor implements ProcessorInterface
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = \PHP_SAPI === 'cli';
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!$this->enabled) {
            return $record;
        }

        $load = sys_getloadavg();

        $record['extra']['load']['1min'] = $load[0];
        $record['extra']['load']['5min'] = $load[1];
        $record['extra']['load']['15min'] = $load[2];

        return $record;
    }
}
