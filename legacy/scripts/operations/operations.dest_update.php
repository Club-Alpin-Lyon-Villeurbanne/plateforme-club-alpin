<?php

use App\Legacy\LegacyContainer;

require __DIR__.'/operations.dest_verif.php';

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    // Sauvegarde d'un nouveau lieu
    if ($lieu) {
        // Save lieu
        $lieu_nom = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_nom);
        $lieu_description = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_description);
        $lieu_ign = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_ign);
        $lieu_lat = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_lat);
        $lieu_lng = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_lng);

        $sql = "INSERT INTO `caf_lieu` (`nom`, `description`, `ign`, `lat`, `lng`)
            VALUES ('$lieu_nom', '$lieu_description', '$lieu_ign', '$lieu_lat', '$lieu_lng');";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
            $errTab[] = 'Erreur SQL lors de la création du lieu';
        } else {
            $id_lieu = $_POST['lieu']['id'] = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
    }
    // On conserve le lieu initialement créé
    else {
        $id_lieu = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_lieu);
    }

    // Mise à jour de la destination
    $id_user_who_create = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_user_who_create);
    $id_user_responsable = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_user_responsable);
    if (null === $id_user_adjoint) {
        $id_user_adjoint = 'NULL';
    } else {
        $id_user_adjoint = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_user_adjoint);
    }
    $nom = LegacyContainer::get('legacy_mysqli_handler')->escapeString($nom);
    $code = LegacyContainer::get('legacy_mysqli_handler')->escapeString($code);
    $description = LegacyContainer::get('legacy_mysqli_handler')->escapeString($description);
    $ign = LegacyContainer::get('legacy_mysqli_handler')->escapeString($ign);
    $date = LegacyContainer::get('legacy_mysqli_handler')->escapeString($date);
    $date_fin = LegacyContainer::get('legacy_mysqli_handler')->escapeString($date_fin);
    $cout_transport = LegacyContainer::get('legacy_mysqli_handler')->escapeString((float) $cout_transport);
    $inscription_ouverture = LegacyContainer::get('legacy_mysqli_handler')->escapeString($inscription_ouverture);
    $inscription_fin = LegacyContainer::get('legacy_mysqli_handler')->escapeString($inscription_fin);
    $inscription_locked = LegacyContainer::get('legacy_mysqli_handler')->escapeString($inscription_locked);

    $sql = 'UPDATE `caf_destination` SET '.
        "`id_lieu` = '$id_lieu', `id_user_responsable` = '$id_user_responsable', `id_user_adjoint` = '$id_user_adjoint', ".
        "`nom` = '$nom', `code` = '$code', `description` = '$description', `ign` = '$ign', `date` = '$date', `date_fin` = '$date_fin', `cout_transport` = '$cout_transport', `inscription_ouverture` = '$inscription_ouverture', ".
        "`inscription_fin` = '$inscription_fin', `inscription_locked` = '$inscription_locked'".
        " WHERE `id` = $id";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
        $errTab[] = 'Erreur SQL lors de la modification de la destination';
    } else {
        $id_dest_to_update = $id;
    }

    // Création d'un bus pour la destination
    if ($newbus) {
        foreach ($newbus as $bus) {
            $intitule = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bus['intitule']);
            $places_max = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bus['places_max']);
            $places_disponibles = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bus['places_disponibles']);
            $sql = 'INSERT INTO `caf_bus` (`id_destination`, `intitule`, `places_max`, `places_disponibles`) '.
                "VALUES ('$id_dest_to_update', '$intitule', '$places_max', '$places_disponibles');";
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
                $errTab[] = 'Erreur SQL création du bus';
            }
        }
    }

    // Suppression de BUS __ et __ des points de ramassage associés
    if ($bus_delete) {
        $del_ids = null;
        foreach ($bus_delete as $bus) {
            if (null === $del_ids) {
                $del_ids = $bus;
            } else {
                $del_ids .= ','.$bus;
            }
        }

        $sql = "DELETE FROM `caf_bus` WHERE `id` IN ($del_ids) AND `id_destination` = $id_dest_to_update;";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
            $errTab[] = 'Erreur SQL suppression de bus';
        }

        $sql = "DELETE FROM `caf_bus_lieu_destination` WHERE `id_bus` IN ($del_ids) AND `id_destination` = $id_dest_to_update;";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
            $errTab[] = 'Erreur SQL suppression des points de ramassage';
        }
    }
}
