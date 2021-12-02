<?php

global $kernel;

include __DIR__.'/operations.dest_verif.php';

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    // Sauvegarde d'un nouveau lieu
    if ($lieu) {
        // Save lieu
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
    }
    // On conserve le lieu initialement créé
    else {
        $id_lieu = $mysqli->real_escape_string($id_lieu);
    }

    // Mise à jour de la destination
    $id_user_who_create = $mysqli->real_escape_string($id_user_who_create);
    $id_user_responsable = $mysqli->real_escape_string($id_user_responsable);
    if (null === $id_user_adjoint) {
        $id_user_adjoint = 'NULL';
    } else {
        $id_user_adjoint = $mysqli->real_escape_string($id_user_adjoint);
    }
    $nom = $mysqli->real_escape_string($nom);
    $code = $mysqli->real_escape_string($code);
    $description = $mysqli->real_escape_string($description);
    $ign = $mysqli->real_escape_string($ign);
    $date = $mysqli->real_escape_string($date);
    $date_fin = $mysqli->real_escape_string($date_fin);
    $cout_transport = $mysqli->real_escape_string((float) $cout_transport);
    $inscription_ouverture = $mysqli->real_escape_string($inscription_ouverture);
    $inscription_fin = $mysqli->real_escape_string($inscription_fin);
    $inscription_locked = $mysqli->real_escape_string($inscription_locked);

    $sql = 'UPDATE `caf_destination` SET '.
        "`id_lieu` = '$id_lieu', `id_user_responsable` = '$id_user_responsable', `id_user_adjoint` = '$id_user_adjoint', ".
        "`nom` = '$nom', `code` = '$code', `description` = '$description', `ign` = '$ign', `date` = '$date', `date_fin` = '$date_fin', `cout_transport` = '$cout_transport', `inscription_ouverture` = '$inscription_ouverture', ".
        "`inscription_fin` = '$inscription_fin', `inscription_locked` = '$inscription_locked'".
        " WHERE `id` = $id";
    if (!$mysqli->query($sql)) {
        $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
            'error' => $mysqli->error,
            'file' => __FILE__,
            'line' => __LINE__,
            'sql' => $sql,
        ]);
        $errTab[] = 'Erreur SQL lors de la modification de la destination';
    } else {
        $id_dest_to_update = $id;
    }

    // Création d'un bus pour la destination
    if ($newbus) {
        foreach ($newbus as $bus) {
            $intitule = $mysqli->real_escape_string($bus['intitule']);
            $places_max = $mysqli->real_escape_string($bus['places_max']);
            $places_disponibles = $mysqli->real_escape_string($bus['places_disponibles']);
            $sql = 'INSERT INTO `caf_bus` (`id`, `id_destination`, `intitule`, `places_max`, `places_disponibles`) '.
                "VALUES (NULL, '$id_dest_to_update', '$intitule', '$places_max', '$places_disponibles');";
            if (!$mysqli->query($sql)) {
                $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                    'error' => $mysqli->error,
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'sql' => $sql,
                ]);
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
        if (!$mysqli->query($sql)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $sql,
            ]);
            $errTab[] = 'Erreur SQL suppression de bus';
        }

        $sql = "DELETE FROM `caf_bus_lieu_destination` WHERE `id_bus` IN ($del_ids) AND `id_destination` = $id_dest_to_update;";
        if (!$mysqli->query($sql)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $sql,
            ]);
            $errTab[] = 'Erreur SQL suppression des points de ramassage';
        }
    }
}
