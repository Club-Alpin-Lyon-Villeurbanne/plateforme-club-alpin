<?php

global $kernel;

include __DIR__.'/operations.bus_verif.php';

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    if ($lieu) {
        $lieu_nom = $mysqli->real_escape_string($lieu_nom);
        $lieu_description = $mysqli->real_escape_string($lieu_description);
        $lieu_ign = $mysqli->real_escape_string($lieu_ign);
        $lieu_lat = $mysqli->real_escape_string($lieu_lat);
        $lieu_lng = $mysqli->real_escape_string($lieu_lng);

        $sql = "INSERT INTO `caf_lieu` (`id`, `nom`, `description`, `ign`, `lat`, `lng`)
            VALUES (NULL, '$lieu_nom', '$lieu_description', '$lieu_ign', '$lieu_lat', '$lieu_lng');";
        if (!$mysqli->query($sql)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $sql,
            ]);
            $errTab[] = 'Erreur SQL lors de la création du lieu';
        } else {
            $id_lieu = $_POST['lieu']['id'] = $mysqli->insert_id;
        }
    } elseif ($id_lieu) {
        $id_lieu = $mysqli->real_escape_string($id_lieu);
    }

    if ($id_lieu) {
        // enregistre bus dest lieu
        $bdl_id_bus = $mysqli->real_escape_string($bdl_id_bus);
        $bdl_id_destination = $mysqli->real_escape_string($bdl_id_destination);
        $id_lieu = $mysqli->real_escape_string($id_lieu);
        $bdl_type_lieu = $mysqli->real_escape_string($bdl_type_lieu);
        $bdl_date = $mysqli->real_escape_string($bdl_date);

        $req = 'INSERT INTO `caf_bus_lieu_destination` (`id`, `id_bus`, `id_destination`, `id_lieu`, `type_lieu`, `date`) VALUES '.
            "(NULL, '$bdl_id_bus', '$bdl_id_destination', '$id_lieu', '$bdl_type_lieu', '$bdl_date');";
        if (!$mysqli->query($req)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $req,
            ]);
            $errTab[] = "Erreur SQL lors de la sauvegarde de l'association bus / lieu / destination";
        } else {
            unset($_POST['lieu']);
        }
    }

    $bus_id = $mysqli->real_escape_string($bus_id);
    $intitule = $mysqli->real_escape_string($intitule);
    $places_max = $mysqli->real_escape_string($places_max);
    $sql = "UPDATE `caf_bus` SET `intitule` = '$intitule', `places_max` = '$places_max' WHERE `id` = $bus_id;";
    if (!$mysqli->query($sql)) {
        $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
            'error' => $mysqli->error,
            'file' => __FILE__,
            'line' => __LINE__,
            'sql' => $sql,
        ]);
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

        $id_destination = $mysqli->real_escape_string($id_destination);

        $sql = "DELETE FROM `caf_bus_lieu_destination` WHERE `id` IN ($del_ids) AND `id_destination` = $id_destination AND `id_bus` = $bus_id;";
        if (!$mysqli->query($sql)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $sql,
            ]);
            $errTab[] = 'Erreur SQL suppression de point de ramassage';
        }

        // + suppression de tous les lieux associés au BUS / DESTINATION
    }
}
