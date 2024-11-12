<?php

use App\Legacy\LegacyContainer;

// MODIFIE LA VAR HANDLE
// NEED SQL CONNECT

// possibilités d'inscription ?
$handle['temoin'] = '';
$handle['temoin-title'] = '';

// compter tous les participants, y compris les encadrants
$req = 'SELECT COUNT(id_evt_join) FROM caf_evt_join
            WHERE status_evt_join =1
            AND evt_evt_join =' . (int) $handle['id_evt'] . '
            ORDER BY caf_evt_join.id_evt_join ASC';
$handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$count = getArrayFirstValue($handleSql2->fetch_array(\MYSQLI_NUM));

// suite de cycle
if ($handle['cycle_parent_evt']) {
    $handle['temoin'] = '';
    $handle['temoin-title'] = 'Les inscriptions pour cette sortie ont lieu dans la première sortie du cycle';
}

// annulé ?
elseif (isset($handle['cancelled_evt']) && $handle['cancelled_evt']) {
    $handle['temoin'] = 'off';
    $handle['temoin-title'] = 'Cette sortie est annulée';
}

// trop tard ?
elseif (time() > $handle['tsp_evt'] - (24 * 60 * 60)) { // date max d'inscri. 24 h
    $handle['temoin'] = 'off';
    $handle['temoin-title'] = 'Les inscriptions sont terminées';
}

// inscriptions pas encore commencées
elseif (time() < $handle['join_start_evt']) {
    $handle['temoin'] = '';
    $handle['temoin-title'] = 'Les inscriptions pour cette sortie commenceront le ' . date('d/m/y', $handle['join_start_evt']);
} else {
    // inscriptions pleines
    if (isset($handle['ngens_max_evt']) && $count >= $handle['ngens_max_evt']) {// inscriptions max
        $handle['temoin'] = 'off';
        $handle['temoin-title'] = 'Les ' . $handle['ngens_max_evt'] . ' places libres ont été réservées';
    } else {
        $handle['temoin'] = 'on';
        $handle['temoin-title'] = (($handle['ngens_max_evt'] ?? 0) - $count) . ' places restantes';
    }
}
