<?php

require_once __DIR__.'/../../vendor/autoload.php';

$config = require __DIR__.'/../config/config.php';

if (isset($config['sentry_dsn'])) {
    Sentry\init([
        'dsn' => $config['sentry_dsn'],
    ]);
}

if (\PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_HOST']) && $config['https'] && !isset($_SERVER['HTTPS'])) {
    header('Location: '.$p_racine = 'https://'.$_SERVER['HTTP_HOST'], true, 301);
    exit;
}

$scriptsDir = __DIR__.'/../scripts/';

/*
    Ce script ne fonctionnera plus le jour où plusieurs sites seront hébergés dans le même ROOT, car il existera plusieurs dossiers de configuration dans CONFIG.
    Il faudra alors définir un paramètre pour les tâches CRON !
*/
if ($_SERVER && array_key_exists('HTTP_HOST', $_SERVER)) {
    define('MON_DOMAINE', $_SERVER['HTTP_HOST']);
} else {
    $config = require __DIR__.'/../config/config.php';
    define('MON_DOMAINE', parse_url($config['url'], \PHP_URL_HOST));
}

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
