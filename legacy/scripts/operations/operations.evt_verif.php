<?php

use App\Legacy\LegacyContainer;

if ('evt_create' == ($_POST['operation'] ?? null)) {
    $user_evt = getUser()->getId();
    $tsp_crea_evt = time();
    $benevoles = isset($_POST['benevoles']) && is_array($_POST['benevoles']) ? $_POST['benevoles'] : [];
}
if ('evt_update' == ($_POST['operation'] ?? null)) {
    $id_evt = (int) $_POST['id_evt_to_update'];
}
$commission_evt = (int) $_POST['commission_evt'];
$id_groupe = (int) $_POST['id_groupe'];
$titre_evt = trim(stripslashes($_POST['titre_evt']));

$tarif_evt = (float) $_POST['tarif_evt'];
$tarif_evt = str_replace(',', '.', $tarif_evt);
$tarif_detail = $_POST['tarif_detail'] = trim(stripslashes($_POST['tarif_detail']));

$massif_evt = trim(stripslashes($_POST['massif_evt']));
$rdv_evt = trim(stripslashes($_POST['rdv_evt']));
$lat_evt = (float) $_POST['lat_evt'];
$lat_evt = str_replace(',', '.', $lat_evt);
$long_evt = (float) $_POST['long_evt'];
$long_evt = str_replace(',', '.', $long_evt);
$tsp_evt_day = trim(stripslashes($_POST['tsp_evt_day']));
$tsp_evt_hour = trim(stripslashes($_POST['tsp_evt_hour']));
$tsp_end_evt_day = trim(stripslashes($_POST['tsp_end_evt_day']));
$tsp_end_evt_hour = '23:59';

$denivele_evt = trim(stripslashes($_POST['denivele_evt']));
$distance_evt = trim(stripslashes($_POST['distance_evt']));
$distance_evt = str_replace(',', '.', $distance_evt);
$matos_evt = trim(stripslashes($_POST['matos_evt']));
$itineraire = trim(stripslashes($_POST['itineraire']));
$difficulte_evt = substr(trim(stripslashes($_POST['difficulte_evt'])), 0, 50);
$description_evt = trim(stripslashes($_POST['description_evt']));
$need_benevoles_evt = isset($_POST['need_benevoles_evt']) && 'on' == $_POST['need_benevoles_evt'] ? 1 : 0;

// inscriptions
$join_start_evt_days = 10;
$join_max_evt = (int) $_POST['join_max_evt'];
$ngens_max_evt = (int) $_POST['ngens_max_evt'];
// tableaux
$encadrants = isset($_POST['encadrants']) && is_array($_POST['encadrants']) ? $_POST['encadrants'] : [];
$coencadrants = isset($_POST['coencadrants']) && is_array($_POST['coencadrants']) ? $_POST['coencadrants'] : [];
$stagiaires = isset($_POST['stagiaires']) && is_array($_POST['stagiaires']) ? $_POST['stagiaires'] : [];

if ('evt_create' == ($_POST['operation'] ?? null)) {
    if (!$user_evt) {
        $errTab[] = 'ID user invalide';
    }
}
if ('evt_update' == ($_POST['operation'] ?? null)) {
    if (!$id_evt) {
        $errTab[] = 'ID événement invalide';
    }
}

// Vérifications
if (!$commission_evt) {
    $errTab[] = 'ID commission invalide';
}
if (strlen($titre_evt) < 10 || strlen($titre_evt) > 100) {
    $errTab[] = "Merci d'entrer un titre pour cette sortie, de 10 à 100 caractères";
}
if (!count($encadrants) && !count($coencadrants) && !count($stagiaires)) {
    $errTab[] = 'Veuillez sélectionner au moins un encadrant ou co-encadrant';
}
if (strlen($description_evt) < 3) {
    $errTab[] = "Merci d'entrer une description pour cette sortie";
}
if ($ngens_max_evt < $join_max_evt) {
    $errTab[] = "Il devrait y avoir davantage de places totales que de possibilités d'inscriptions";
}

if (!$join_start_evt_days) {
    $errTab[] = 'Merci de préciser le nombre de jours avant la sortie, pour les inscriptions';
}
if (strlen($rdv_evt) < 3 || strlen($rdv_evt) > 200) {
    $errTab[] = "Merci d'entrer un lieu de rendez-vous de 3 à 200 caractères";
}
if (!$lat_evt || !$long_evt || 1 == $lat_evt || 1 == $long_evt) {
    $errTab[] = 'Coordonnées introuvables. Vérifiez le positionnement du curseur sur la carte.';
}
if (!preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $tsp_evt_day)) {
    $errTab[] = 'La date du rendez-vous doit être au format jj/mm/aaaa.';
}
if (!preg_match('#[0-9]{2}:[0-9]{2}#', $tsp_evt_hour)) {
    $errTab[] = "L'heure du rendez-vous doit être au format hh:mm.";
}
if (!preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $tsp_end_evt_day)) {
    $errTab[] = 'La date de fin doit être au format jj/mm/aaaa.';
}
if (!preg_match('#[0-9]{2}:[0-9]{2}#', $tsp_end_evt_hour)) {
    $errTab[] = "L'heure de fin doit être au format hh:mm.";
}

// vérifications des dates
if (!isset($errTab) || 0 === count($errTab)) {
    // checks dates
    $copy_depose_to_reprise = false;

    // tsp de début
    $tab = explode('/', $tsp_evt_day);
    $tab2 = explode(':', $tsp_evt_hour);
    $tsp_evt = mktime($tab2[0], $tab2[1], 0, $tab[1], $tab[0], $tab[2]);

    // génération du timestamp de départ des inscriptions : join_start_evt devient un timestamp
    $join_start_evt = $tsp_evt - ($join_start_evt_days * 60 * 60 * 24);
    $join_start_evt = mktime(0, 0, 0, date('n', $join_start_evt), date('j', $join_start_evt), date('Y', $join_start_evt));

    // tsp de fin
    $tab = explode('/', $tsp_end_evt_day);
    $tab2 = explode(':', $tsp_end_evt_hour);
    $tsp_end_evt = mktime($tab2[0], $tab2[1], 0, $tab[1], $tab[0], $tab[2]);

    if ($join_start_evt_days <= 1 || $join_start_evt > $tsp_evt) {
        $errTab[] = "Vérifiez les dates d'inscription : vous devez entrer un nombre de jours supérieur ou égal à 2 pour les délais d'inscriptions.";
    }
}

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    // *** necessité de récupérer le code de cette commission
    $code_commission = 'ERR';
    $req = "SELECT code_commission FROM caf_commission WHERE id_commission=$commission_evt LIMIT 0 , 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $code_commission = $row['code_commission'];
    }

    // *** check chaque encadrant lié : Est-il bien autorisé à encadrer pour cette commission ?
    // (anti piratage : Evite de passer en force un ID d'utilisateur non autorisé)
    if (!isset($errTab) || 0 === count($errTab)) {
        // encadrant :
        foreach ($encadrants as $id_user) {
            $id_user = (int) $id_user;
            $req = ''
                . 'SELECT COUNT(id_user_attr) ' // le résultat est >1 si l'user a les droits
                . 'FROM caf_usertype, caf_user_attr ' // dans la liste des droits > attr_droit_type > type > attr_type_user
                . "WHERE user_user_attr=$id_user " // de user à user_attr
                . "AND code_usertype LIKE 'encadrant' " // droit
                . "AND params_user_attr LIKE 'commission:$code_commission' " // droit donné pour cette commission unqiuement
                . 'AND usertype_user_attr=id_usertype ' // de user_attr à usertype
            ;
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            if (!$row[0]) {
                $errTab[] = "Erreur, il semble que vous ayez lié un encadrant non autorisé. ID_encadrant=$id_user et commission:$code_commission.";
            }
        }
        // stagiaire :
        foreach ($stagiaires as $id_user) {
            $id_user = (int) $id_user;
            $req = ''
                . 'SELECT COUNT(id_user_attr) ' // le résultat est >1 si l'user a les droits
                . 'FROM caf_usertype, caf_user_attr ' // dans la liste des droits > attr_droit_type > type > attr_type_user
                . "WHERE user_user_attr=$id_user " // de user à user_attr
                . "AND code_usertype LIKE 'stagiaire'" // droit
                . "AND params_user_attr LIKE 'commission:$code_commission' " // droit donné pour cette commission unqiuement
                . 'AND usertype_user_attr=id_usertype ' // de user_attr à usertype
            ;
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            if (!$row[0]) {
                $errTab[] = "Erreur, il semble que vous ayez lié un encadrant non autorisé. ID_encadrant=$id_user et commission:$code_commission.";
            }
        }
        // coencadrant :
        foreach ($coencadrants as $id_user) {
            $id_user = (int) $id_user;
            $req = ''
                . 'SELECT COUNT(id_user_attr) ' // le résultat est >1 si l'user a les droits
                . 'FROM caf_usertype, caf_user_attr ' // dans la liste des droits > attr_droit_type > type > attr_type_user
                . "WHERE user_user_attr=$id_user " // de user à user_attr
                . "AND code_usertype LIKE 'coencadrant' " // droit
                . "AND params_user_attr LIKE 'commission:$code_commission' " // droit donné pour cette commission unqiuement
                . 'AND usertype_user_attr=id_usertype ' // de user_attr à usertype
            ;
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            if (!$row[0]) {
                $errTab[] = "Erreur, il semble que vous ayez lié un co-encadrant non autorisé. Id=$id_user et commission:$code_commission.";
            }
        }
        // benevole :
        if ('evt_create' == ($_POST['operation'] ?? null)) {
            foreach ($benevoles as $id_user) {
                $id_user = (int) $id_user;
                $req = ''
                    . 'SELECT COUNT(id_user_attr) ' // le résultat est >1 si l'user a les droits
                    . 'FROM caf_usertype, caf_user_attr ' // dans la liste des droits > attr_droit_type > type > attr_type_user
                    . "WHERE user_user_attr=$id_user " // de user à user_attr
                    . "AND code_usertype LIKE 'benevole' " // droit
                    . "AND params_user_attr LIKE 'commission:$code_commission' " // droit donné pour cette commission unqiuement
                    . 'AND usertype_user_attr=id_usertype ' // de user_attr à usertype
                ;
                $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                $row = $result->fetch_row();
                if (!$row[0]) {
                    $errTab[] = "Erreur, il semble que vous ayez lié un benevole non autorisé. Id=$id_user et commission:$code_commission.";
                }
            }
        }
    }
}

/*
echo '<pre>';
print_r($_POST);

echo '</pre>';
die;$errTab[] = 'Erreur manuelle pour ne pas enregistrer';
*/
