<?php

namespace App\Bridge\Monolog\SentryHandlerLogFilter;

interface LogFilterInterface
{
    public function shouldSkip(array $record): bool;
}
