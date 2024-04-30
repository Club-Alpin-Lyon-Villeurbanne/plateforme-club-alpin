<?php

use App\Legacy\LegacyContainer;

// commission courante sur cette page
$current_commission = false;

// LISTE DES COMMISSIONS PUBLIQUES
$req = 'SELECT * FROM caf_commission WHERE vis_commission=1 ORDER BY ordre_commission ASC';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$comTab = [];
$comCodeTab = [];
while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // v2 :
    $comTab[$handle['code_commission']] = $handle;

    // définition de la variable de page 'current_commission' si elle est précisée dans l'URL
    if ($p2 == $handle['code_commission']) {
        $current_commission = $p2;
    }
    // variable de commission si elle est passée "en force" dans les vars GET
    elseif (($_GET['commission'] ?? null) == $handle['code_commission']) {
        $current_commission = $_GET['commission'];
    }
}