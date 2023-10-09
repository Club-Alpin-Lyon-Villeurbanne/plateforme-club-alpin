<?php

return [
    'sentry_dsn' => 'https://eaa373582efe4133aa456361e19f0518@o1046113.ingest.sentry.io/6021900',
    'https' => true,
    'url' => 'https://www.clubalpinlyon.fr',
    'use_smtp' => true,
    'smtp_conf' => [
        'host' => 'smtp.eu.mailgun.org',
        'port' => 465,
        'ssl' => true,
        'user' => 'postmaster@mg.clubalpinlyon.fr',
        'pass' => 'dc23887c4a62479ee5b0a7d32932ac0a-7005f37e-b40a7863',
    ],
    'db' => [
        'host' => 'caflv-production-aurora-mysql.cluster-cw75ek4t1pty.eu-west-3.rds.amazonaws.com',
        'port' => 3306,
        'user' => 'root',
        'password' => 'DB_PASSWORD_TO_REPLACE',
        'dbname' => 'caflvproduction',
    ],
];
