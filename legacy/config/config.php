<?php

return [
    'sentry_dsn' => null,
    'https' => false,
    'url' => $_ENV['ROUTER_CONTEXT_HOST'],
    'use_smtp' => true,
    'smtp_conf' => [
        'host' => $_ENV['SMTP_HOST'],
        'port' => $_ENV['SMTP_PORT'],
        'ssl' => false,
        'user' => null,
        'pass' => null,
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
        'dbname' => $_ENV['DB_NAME'],
    ],
];
