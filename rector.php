<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\ValueObject\PhpVersion;

/*
 * Configuration volontairement minimale pour une première mise en place.
 *
 * Le but est d'avoir un outil installé, lancé en CI et vert, pas de réécrire
 * le projet d'un coup : activer `withPhpSets(php83: true)` produit aujourd'hui
 * 234 changements sur 139 fichiers (promotion de constructeur, readonly,
 * arrow functions...). Ces sets sont à activer un par un, dans des PR dédiées
 * et relisibles.
 *
 * Le code `legacy/` est hors périmètre : il n'est pas couvert par les tests.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/src/Kernel.php', // fichier généré par Symfony
    ])
    // Le projet tourne en PHP 8.3 ; on vise déjà 8.4 pour que la règle ci-dessous
    // s'applique. La syntaxe qu'elle produit (`?Type $x = null`) est valide depuis
    // PHP 7.1, il n'y a donc aucun risque à anticiper ici.
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withRules([
        // `Type $x = null` est déprécié à partir de PHP 8.4 : on écrit `?Type $x = null`.
        ExplicitNullableParamTypeRector::class,
    ])
    // Les niveaux Rector sont progressifs : chaque cran ajoute des règles.
    // On démarre bas pour que la CI soit verte tout de suite ; la montée se fait
    // cran par cran, dans des PR dédiées.
    ->withDeadCodeLevel(3)
    ->withCodeQualityLevel(3)
    ->withCache(__DIR__ . '/var/rector')
    ->withParallel();
