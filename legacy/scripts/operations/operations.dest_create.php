<?php

use App\Legacy\LegacyContainer;

require __DIR__.'/operations.dest_verif.php';

// vérifications BDD
if (!isset($errTab) || 0 === count($errTab)) {
    // Vérification des (co)responsables

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

        $sql = 'INSERT INTO `caf_destination` '.
            '(`id_lieu`, `id_user_who_create`, `id_user_responsable`, `id_user_adjoint`, `nom`, `code`, `description`, `ign`, `date`, `date_fin`, `cout_transport`, `inscription_ouverture`, `inscription_fin`, `inscription_locked`) '.
            "VALUES ('$id_lieu', '$id_user_who_create', '$id_user_responsable','$id_user_adjoint', '$nom', '$code', '$description', '$ign', '$date', '$date_fin', '$cout_transport', '$inscription_ouverture', '$inscription_fin', '$inscription_locked');";

        if (!LegacyContainer::get('legacy_mysqli_handler')->query($sql)) {
            $errTab[] = 'Erreur SQL lors de la création de la destination : ';
        } else {
            $id_dest_to_update = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    header('Location: /creer-une-sortie/creer-une-destination/update-'.$id_dest_to_update.'.html');
}
