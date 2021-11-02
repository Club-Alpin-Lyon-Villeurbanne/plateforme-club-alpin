<?php

$configParamsLocal = CONFIG.MON_DOMAINE.DS.basename(__FILE__);

if (file_exists($configParamsLocal)) {
    include $configParamsLocal;
} else {
    $configParams = CONFIG.basename(__FILE__);
    if (file_exists($configParams)) {
        include $configParams;
    } else {
        if (MON_DOMAINE === 'clubalpinlyon.fr') {
            header('Location: https://www.clubalpinlyon.fr', true, 301);
            exit;
        }
        header('HTTP/1.0 404 Not Found');
        exit('Aucun fichier de configuration "'.CONFIG.MON_DOMAINE.DS.basename(__FILE__)."\" n'a été trouvé");
    }
}
