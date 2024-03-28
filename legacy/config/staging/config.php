<?php

return [
    'sentry_dsn' => 'https://6d27eabb1f5b4a2c90922b00e472c034@o1046113.ingest.sentry.io/6600048',
    'https' => true,
    'url' => 'https://www.clubalpinlyon.top',
    'use_smtp' => true,
    'smtp_conf' => [
        'host' => 'smtp.eu.mailgun.org',
        'port' => 465,
        'ssl' => true,
        'user' => 'postmaster@mg.clubalpinlyon.fr',
        'pass' => 'MAILGUN_KEY_TO_REPLACE',
    ],
    'db' => [
        'host' => getenv('MYSQL_ADDON_HOST'),
        'port' => getenv('MYSQL_ADDON_PORT'),
        'user' => getenv('MYSQL_ADDON_USER'),
        'password' => getenv('MYSQL_ADDON_PASSWORD'),
        'dbname' => getenv('MYSQL_ADDON_DB'),
    ],
];
