<?php

return [
    'sentry_dsn' => null,
    'https' => false,
    'url' => 'http://cafsite.caf',
    'use_smtp' => true,
    'smtp_conf' => [
        'host' => 'mail_caflyon',
        'port' => '1025',
        'ssl' => false,
        'user' => null,
        'pass' => null,
    ],
    'db' => [
        'host' => 'db_caflyon',
        'port' => 3306,
        'user' => 'root',
        'password' => 'test',
        'dbname' => 'caf',
    ],
];
