<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    global $kernel;

    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return $kernel;
};
