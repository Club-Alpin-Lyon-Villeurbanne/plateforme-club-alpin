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
include __DIR__.'/../app/fonctions.php';
//_________________________________________________ VARIABLES "GLOBALES" DU SITE
include __DIR__.'/../config/params.php';
//_________________________________________________ LANGUES
include __DIR__.'/../app/langs.php';
//_________________________________________________ FONCTIONS PARTAGEES
include __DIR__.'/../scripts/fonctions.php';
//_________________________________________________ OPERATIONS ADMIN & CLIENT
include __DIR__.'/../scripts/operations.php';
//_________________________________________________ PARAMS PAGE EN COURS (META/TITRES/EXIST.)
include __DIR__.'/../app/pages.php';
//_________________________________________________ REQUETES INHERENTES A LA PAGE
include __DIR__.'/../scripts/reqs.php';
