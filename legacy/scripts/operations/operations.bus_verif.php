<?php

$bus_id = (int) ($_POST['id']);

$places_max = (int) ($_POST['places_max']);
if (0 == $places_max) {
    $errTab[] = 'Le bus doit avoir au moins 1 place';
}
$id_destination = (int) ($_POST['id_destination']);
// Attention si places max ><>< places dispos

$intitule = trim(stripslashes($_POST['intitule']));
if (empty($intitule) || strlen($intitule) > 100) {
    $errTab[] = "Merci d'entrer un intitulé pour ce bus, 100 caractères max";
}

if ($_POST['bus_dest_lieu']) {
    $bdl = $_POST['bus_dest_lieu'];
    $bdl_id_bus = (int) ($bdl['id_bus']);
    $bdl_id_destination = (int) ($bdl['id_destination']);
    $bdl_type_lieu = trim(stripslashes($bdl['type_lieu']));
    $bdl_date = trim(stripslashes($bdl['date']));

    // regex date
    if (!preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}#', $bdl_date)) {
        $errTab[] = 'La date et horaire de ramassage doit être au format aaaa-mm-dd hh:ii:ss.';
    }
}

if (empty($_POST['use_existant'])) {
    if ($_POST['lieu']) {
        $lieu = $_POST['lieu'];
        $lieu_nom = trim(stripslashes($lieu['nom']));
        if (empty($lieu_nom)) {
            $errTab[] = 'Vérifiez le nom du lieu, il ne peut être vide.';
        }
        $lieu_description = trim(stripslashes($lieu['description']));
        $lieu_ign = trim(stripslashes(get_iframe_src($lieu['ign'])));
        $lieu_lat = (float) ($lieu['lat']);
        $lieu_lat = str_replace(',', '.', $lieu_lat);
        $lieu_lng = (float) ($lieu['lng']);
        $lieu_lng = str_replace(',', '.', $lieu_lng);
    }
} else {
    $id_lieu = (int) ($_POST['use_existant']);
}

if (!isset($errTab) || 0 === count($errTab)) {
    // Vérifier que l'horaire de ramassage est bien le jour de la destination
}

if ($_POST['lieu_ramasse_delete']) {
    $lieu_ramasse_delete = $_POST['lieu_ramasse_delete'];
}

// print_r($errTab);
