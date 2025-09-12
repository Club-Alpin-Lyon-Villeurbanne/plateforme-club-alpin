<?php

use App\Legacy\LegacyContainer;

require_once __DIR__ . '/../../vendor/autoload.php';

if (LegacyContainer::getParameter('legacy_env_SENTRY_DSN')) {
    Sentry\init([
        'dsn' => LegacyContainer::getParameter('legacy_env_SENTRY_DSN'),
    ]);
}

$scriptsDir = __DIR__ . '/../scripts/';

// _________________________________________________ FONCTIONS MAISON
require __DIR__ . '/../app/fonctions.php';
// _________________________________________________ VARIABLES "GLOBALES" DU SITE
require __DIR__ . '/../app/params.php';
// _________________________________________________ FONCTIONS PARTAGEES
require __DIR__ . '/../scripts/fonctions.php';
// _________________________________________________ OPERATIONS ADMIN & CLIENT
require __DIR__ . '/../scripts/operations.php';
// _________________________________________________ PARAMS PAGE EN COURS (META/TITRES/EXIST.)
require __DIR__ . '/../app/pages.php';
// _________________________________________________ REQUETES INHERENTES A LA PAGE
require __DIR__ . '/../scripts/get_commissions.php';
