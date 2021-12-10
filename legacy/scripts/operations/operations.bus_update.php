<?php

use App\Legacy\LegacyContainer;

require __DIR__.'/operations.bus_verif.php';

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    if ($lieu) {
        $lieu_nom = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_nom);
        $lieu_description = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_description);
        $lieu_ign = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_ign);
        $lieu_lat = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_lat);
        $lieu_lng = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lieu_lng);

        $sql = "INSERT INTO `caf_lieu` (`id`, `nom`, `description`, `ign`, `lat`, `lng`)
            VALUES (NULL, '$lieu_nom', '$lieu_description', '$lieu_ign', '$lieu_lat', '$lieu_lng');";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
            $errTab[] = 'Erreur SQL lors de la création du lieu';
        } else {
            $id_lieu = $_POST['lieu']['id'] = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
    } elseif ($id_lieu) {
        $id_lieu = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_lieu);
    }

    if ($id_lieu) {
        // enregistre bus dest lieu
        $bdl_id_bus = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bdl_id_bus);
        $bdl_id_destination = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bdl_id_destination);
        $id_lieu = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_lieu);
        $bdl_type_lieu = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bdl_type_lieu);
        $bdl_date = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bdl_date);

        $req = 'INSERT INTO `caf_bus_lieu_destination` (`id`, `id_bus`, `id_destination`, `id_lieu`, `type_lieu`, `date`) VALUES '.
            "(NULL, '$bdl_id_bus', '$bdl_id_destination', '$id_lieu', '$bdl_type_lieu', '$bdl_date');";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = "Erreur SQL lors de la sauvegarde de l'association bus / lieu / destination";
        } else {
            unset($_POST['lieu']);
        }
    }

    $bus_id = LegacyContainer::get('legacy_mysqli_handler')->escapeString($bus_id);
    $intitule = LegacyContainer::get('legacy_mysqli_handler')->escapeString($intitule);
    $places_max = LegacyContainer::get('legacy_mysqli_handler')->escapeString($places_max);
    $sql = "UPDATE `caf_bus` SET `intitule` = '$intitule', `places_max` = '$places_max' WHERE `id` = $bus_id;";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
        $errTab[] = 'Erreur SQL lors de la modification du bus ';
    }

    if ($lieu_ramasse_delete) {
        $del_ids = null;
        foreach ($lieu_ramasse_delete as $lrd) {
            if (null === $del_ids) {
                $del_ids = $lrd;
            } else {
                $del_ids .= ','.$lrd;
            }
        }

        $id_destination = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_destination);

        $sql = "DELETE FROM `caf_bus_lieu_destination` WHERE `id` IN ($del_ids) AND `id_destination` = $id_destination AND `id_bus` = $bus_id;";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
            $errTab[] = 'Erreur SQL suppression de point de ramassage';
        }

        // + suppression de tous les lieux associés au BUS / DESTINATION
    }
}
