<?php

require_once __DIR__.'/../../vendor/autoload.php';

$config = require __DIR__.'/../config/config.php';

if (isset($config['sentry_dsn'])) {
    Sentry\init([
        'dsn' => $config['sentry_dsn'],
    ]);
}

$scriptsDir = __DIR__.'/../scripts/';

//_________________________________________________ FONCTIONS MAISON
require __DIR__.'/../app/fonctions.php';
//_________________________________________________ VARIABLES "GLOBALES" DU SITE
require __DIR__.'/../config/params.php';
//_________________________________________________ FONCTIONS PARTAGEES
require __DIR__.'/../scripts/fonctions.php';
//_________________________________________________ OPERATIONS ADMIN & CLIENT
require __DIR__.'/../scripts/operations.php';
//_________________________________________________ PARAMS PAGE EN COURS (META/TITRES/EXIST.)
require __DIR__.'/../app/pages.php';
//_________________________________________________ REQUETES INHERENTES A LA PAGE
require __DIR__.'/../scripts/reqs.php';
