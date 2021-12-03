<?php

return [
    'sentry_dsn' => 'https://eaa373582efe4133aa456361e19f0518@o1046113.ingest.sentry.io/6021900',
    'https' => true,
    'url' => 'https://test.clubalpinlyon.fr',
    'use_smtp' => false,
    'smtp_conf' => [
        'host' => 'smtp.eu.mailgun.org',
        'port' => '465',
        'ssl' => true,
        'user' => 'postmaster@mg.clubalpinlyon.fr',
        'pass' => 'MAILCHIMP_PASSWORD_TO_REPLACE',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'DB_USER_TO_REPLACE',
        'password' => 'DB_PASSWORD_TO_REPLACE',
        'dbname' => 'DB_NAME_TO_REPLACE',
    ],
];
