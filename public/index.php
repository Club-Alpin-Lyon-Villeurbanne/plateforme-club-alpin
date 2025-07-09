<?php

use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    global $kernel;

    // PATCH TEMPORAIRE - Clever Cloud bug 2025-07-09
    if (!isset($context['APP_ENV'])) {
        $context['APP_ENV'] = getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod';
    }
    if (!isset($context['APP_DEBUG'])) {
        $context['APP_DEBUG'] = getenv('APP_DEBUG') ?: $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? '0';
    }

    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return $kernel;
};
