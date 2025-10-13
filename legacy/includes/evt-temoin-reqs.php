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

// brouillon ?
if (isset($handle['is_draft']) && $handle['is_draft']) {
    $handle['temoin'] = 'draft';
    $handle['temoin-title'] = 'Cette sortie est un brouillon';
}

// annulé ?
elseif (isset($handle['cancelled_evt']) && $handle['cancelled_evt']) {
    $handle['temoin'] = 'full';
    $handle['temoin-title'] = 'Cette sortie est annulée';
}

// trop tard ?
elseif ((new \DateTimeImmutable()) > new \DateTimeImmutable($handle['event_start_date'])) { // date max d'inscri. 24 h
    $handle['temoin'] = 'finished';
    $handle['temoin-title'] = 'Les inscriptions sont terminées';
}

// inscriptions pas encore commencées
elseif (new \DateTimeImmutable() < new \DateTimeImmutable($handle['join_start_date'])) {
    $handle['temoin'] = 'waiting';
    $handle['temoin-title'] = 'Les inscriptions pour cette sortie commenceront le ' . (new \DateTimeImmutable($handle['join_start_date']))?->format('d/m/y');
} else {
    // inscriptions pleines
    if (isset($handle['ngens_max_evt']) && $count >= $handle['ngens_max_evt']) {// inscriptions max
        $handle['temoin'] = 'full';
        $handle['temoin-title'] = 'Les ' . ($handle['ngens_max_evt'] ?? '') . ' places libres ont été réservées';
    } else {
        $handle['temoin'] = 'free';
        $handle['temoin-title'] = max(0, ($handle['ngens_max_evt'] ?? 0) - $count) . ' places restantes';
    }
}
