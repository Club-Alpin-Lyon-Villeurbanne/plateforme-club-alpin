<?php

return [
    'sentry_dsn' => null, // utilisée dans : legacy\app\includes.php
    'https' => false, // n'est utilisée NULLE PART dans le projet
    'url' => $_ENV['ROUTER_CONTEXT_HOST'], // n'est utilisée NULLE PART
    'use_smtp' => true, // n'est utilisée NULLE PART
    'smtp_conf' => [ // n'est utilisée NULLE PART
        'host' => $_ENV['SMTP_HOST'],
        'port' => $_ENV['SMTP_PORT'],
        'ssl' => false,
        'user' => null,
        'pass' => null,
    ],
    'db' => [ // utilisé dans : legacy\app\db_config.php
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
        'dbname' => $_ENV['DB_NAME'],
    ],
];
