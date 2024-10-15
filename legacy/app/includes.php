<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityHelpers;

require_once __DIR__ . '/../../vendor/autoload.php';

if (LegacyContainer::getParameter('legacy_env_SENTRY_DSN')) {
    Sentry\init([
        'dsn' => LegacyContainer::getParameter('legacy_env_SENTRY_DSN'),
    ]);
}

// Récupérer le conteneur Symfony
$container = LegacyContainer::get('service_container');

// Récupérer SecurityHelpers à partir du conteneur Symfony
$securityHelpers = $container->get(SecurityHelpers::class);

$scriptsDir = __DIR__ . '/../scripts/';

// _________________________________________________ FONCTIONS MAISON
require __DIR__ . '/../app/fonctions.php';
// _________________________________________________ VARIABLES "GLOBALES" DU SITE
require __DIR__ . '/../config/params.php';
// _________________________________________________ FONCTIONS PARTAGEES
require __DIR__ . '/../scripts/fonctions.php';
// _________________________________________________ OPERATIONS ADMIN & CLIENT
require __DIR__ . '/../scripts/operations.php';
// _________________________________________________ PARAMS PAGE EN COURS (META/TITRES/EXIST.)
require __DIR__ . '/../app/pages.php';
// _________________________________________________ REQUETES INHERENTES A LA PAGE
require __DIR__ . '/../scripts/get_commissions.php';

if ('feuille-de-sortie' == $p1) {
    require __DIR__ . '/../scripts/get_sortie_params.php';
}
