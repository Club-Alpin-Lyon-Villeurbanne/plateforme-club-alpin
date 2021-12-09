<?php

use App\Legacy\LegacyContainer;

$id = $annule = null;

if ($_POST['id_dest_to_update']) {
    $id = (int) ($_POST['id_dest_to_update']);
}
if (!$id) {
    $errTab[] = 'Identifiant manquant.';
}

if ('dest_validate' == $_POST['operation']) {
    if (isset($_POST['publie'])) {
        $publie = (int) ($_POST['publie']);
    }
}
if ('dest_lock' == $_POST['operation'] || 'dest_validate' == $_POST['operation']) {
    if (isset($_POST['inscription_locked'])) {
        $inscription_locked = (int) ($_POST['inscription_locked']);
    }
}
if ('dest_annuler' == $_POST['operation']) {
    if (isset($_POST['annule'])) {
        $annule = (int) ($_POST['annule']);
    }
    if (isset($_POST['msg'])) {
        $msg = trim(stripslashes($_POST['msg']));
        if (empty($msg) && $annule) {
            $errTab[] = "Vous devez saisir un message à destination des inscrits pour leur expliquer la raison de l'annulation.";
        }
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    $comma = null;
    $sql = 'UPDATE `caf_destination` SET ';
    if (isset($publie)) {
        $publie = LegacyContainer::get('legacy_mysqli_handler')->escapeString($publie);
        $sql .= "`publie` = '$publie'".$comma;
        $comma = ', ';
    }
    if (isset($inscription_locked)) {
        $inscription_locked = LegacyContainer::get('legacy_mysqli_handler')->escapeString($inscription_locked);
        $sql .= "`inscription_locked` = '$inscription_locked'".$comma;
        $comma = ', ';
    }
    if (isset($annule)) {
        $annule = LegacyContainer::get('legacy_mysqli_handler')->escapeString($annule);
        $sql .= "`annule` = '$annule'";
    }
    $sql .= " WHERE `id` = $id";

    if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
        $errTab[] = 'Erreur SQL lors de la modification de la destination';
    } else {
        // Envoi de tous les emails
        if (isset($msg)) {
            // On annule aussi les sorties
            // On récupère les sorties liées et non annulées

            // On annule les sorties

            // On envoie les emails
            // TODO ToDo todo @todo
        }
    }
}
