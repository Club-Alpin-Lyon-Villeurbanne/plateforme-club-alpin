<?php

include __DIR__.'/operations.dest_verif.php';

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    // Vérification des (co)responsables

    // Save lieu
    $lieu_nom = $mysqli->real_escape_string($lieu_nom);
    $lieu_description = $mysqli->real_escape_string($lieu_description);
    $lieu_ign = $mysqli->real_escape_string($lieu_ign);
    $lieu_lat = $mysqli->real_escape_string($lieu_lat);
    $lieu_lng = $mysqli->real_escape_string($lieu_lng);

    $sql = 'INSERT INTO `'.$pbd."lieu` (`id`, `nom`, `description`, `ign`, `lat`, `lng`)
        VALUES (NULL, '$lieu_nom', '$lieu_description', '$lieu_ign', '$lieu_lat', '$lieu_lng');";
    if (!$mysqli->query($sql)) {
        $errTab[] = 'Erreur SQL lors de la création du lieu';
    } else {
        $id_lieu = $_POST['lieu']['id'] = $mysqli->insert_id;

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

        $sql = 'INSERT INTO `'.$pbd.'destination` '.
            '(`id`, `id_lieu`, `id_user_who_create`, `id_user_responsable`, `id_user_adjoint`, `nom`, `code`, `description`, `ign`, `date`, `date_fin`, `cout_transport`, `inscription_ouverture`, `inscription_fin`, `inscription_locked`) '.
            "VALUES (NULL, '$id_lieu', '$id_user_who_create', '$id_user_responsable','$id_user_adjoint', '$nom', '$code', '$description', '$ign', '$date', '$date_fin', '$cout_transport', '$inscription_ouverture', '$inscription_fin', '$inscription_locked');";

        if (!$mysqli->query($sql)) {
            $errTab[] = 'Erreur SQL lors de la création de la destination : ';
        } else {
            $id_dest_to_update = $mysqli->insert_id;
        }
    }
}

$mysqli->close;

if (!isset($errTab) || 0 === count($errTab)) {
    header('Location:'.$p_racine.'creer-une-sortie/creer-une-destination/update-'.$id_dest_to_update.'.html');
}
