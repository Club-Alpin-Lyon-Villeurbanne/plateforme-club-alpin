<?php

$id = null;
if ($_POST['id_dest_to_update']) {
    $id = (int) ($_POST['id_dest_to_update']);
}

$id_user_who_create = (int) ($_POST['id_user_who_create']);
$id_user_responsable = (int) ($_POST['id_user_responsable']);
$id_user_adjoint = null;
if ($_POST['id_user_adjoint']) {
    $id_user_adjoint = (int) ($_POST['id_user_adjoint']);
}

$nom = trim(stripslashes($_POST['nom']));
$code = substr(formater($nom, 3), 0, 30);
$date = trim(stripslashes($_POST['date']));
$date_fin = trim(stripslashes($_POST['date_fin']));
$inscription_ouverture = trim(stripslashes($_POST['inscription_ouverture']));
$inscription_fin = trim(stripslashes($_POST['inscription_fin']));
$inscription_locked = ('on' == $_POST['inscription_locked'] ? 1 : 0);
$ign = trim(stripslashes(get_iframe_src($_POST['ign'])));
$description = trim(stripslashes($_POST['description']));

// vérifications
if (strlen($nom) < 10 || strlen($nom) > 100) {
    $errTab[] = "Merci d'entrer un titre pour cette sortie, de 10 à 100 caractères";
}
if (!$id_user_responsable) {
    $errTab[] = 'Il faut un responsable de destination';
}

// regex date
if (!preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}#', $date)) {
    $errTab[] = 'La date doit être au format aaaa-mm-dd hh:ii:ss.';
}
if (!preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2}#', $date_fin)) {
    $errTab[] = 'La date de fin doit être au format aaaa-mm-dd.';
}
if (!preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}#', $inscription_ouverture)) {
    $errTab[] = 'La date de début des inscriptions doit être au format aaaa-mm-dd hh:ii:ss.';
}
if (!preg_match('#[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}#', $inscription_fin)) {
    $errTab[] = 'La date de fin des inscriptions doit être au format aaaa-mm-dd hh:ii:ss.';
}

// vérifications des dates
if (!isset($errTab) || 0 === count($errTab)) {
    $dDate = new DateTime($date);
    $dIo = new DateTime($inscription_ouverture);
    $dFo = new DateTime($inscription_fin);
    $dNow = new DateTime('now');

    if ($dNow > $dDate) {
        $errTab[] = 'Vérifiez la date de la destination, elle ne doit pas être déjà passée.';
    }
    if ($dIo > $dDate) {
        $errTab[] = "Vérifiez la date d'ouverture des inscription, elle ne peut pas être après la date de destination.";
    }
    if ($dIo > $dFo) {
        $errTab[] = "Vérifiez les dates d'inscription, l'ouverture semble être après la fermeture.";
    }
}

// Verification du lieu
// Vérification des bus et tarif de bus
if ('' === $_POST['cout_transport']) {
    $errTab[] = 'Le transport est il vraiment gratuit ? Si oui, saisissez 0 pour le coût.';
} else {
    $cout_transport = (float) ($_POST['cout_transport']);
}

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
} else {
    $id_lieu = (int) ($_POST['id_lieu']);
}

if ($_POST['newbus']) {
    $newbus = $_POST['newbus'];
    foreach ($newbus as $b => $bus) {
        $newbus[$b]['intitule'] = trim(stripslashes($newbus[$b]['intitule']));
        if (empty($newbus[$b]['intitule']) || strlen($newbus[$b]['intitule']) > 100) {
            $errTab[] = "Merci d'entrer un intitulé pour ce bus, 100 caractères max";
        }
        $newbus[$b]['places_max'] = $newbus[$b]['places_disponibles'] = (int) ($newbus[$b]['places_max']);
        if (empty($newbus[$b]['places_max'])) {
            $errTab[] = 'Le bus doit avoir au moins 1 place.';
        }
    }
} else {
    $id_lieu = (int) ($_POST['id_lieu']);
}

if ($_POST['bus_delete']) {
    $bus_delete = $_POST['bus_delete'];
}
