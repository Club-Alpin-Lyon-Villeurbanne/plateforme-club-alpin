<?php

$configParamsLocal = __DIR__.'/../config/'.MON_DOMAINE.'/params.php';
if (file_exists($configParamsLocal)) {
    include $configParamsLocal;
} else {
    $configParams = __DIR__.'/../config/params.php';
    if (file_exists($configParams)) {
        include $configParams;
    } else {
        if (MON_DOMAINE === 'clubalpinlyon.fr') {
            header('Location: https://www.clubalpinlyon.fr', true, 301);
            exit;
        }
        header('HTTP/1.0 404 Not Found');
        exit('Aucun fichier de configuration "'.__DIR__.'/../config/'.MON_DOMAINE.'/params.php'."\" n'a été trouvé");
    }
}
