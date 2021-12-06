<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if ((!isset($_SERVER['https']) || 'on' !== $_SERVER['https']) && str_contains($_SERVER['HTTP_HOST'], 'clubalpinlyon.fr')) {
    header(sprintf('Location: https://%s', $_SERVER['HTTP_HOST']), true, 301);
    exit;
}

return function (array $context) {
    global $kernel;

    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return $kernel;
};
