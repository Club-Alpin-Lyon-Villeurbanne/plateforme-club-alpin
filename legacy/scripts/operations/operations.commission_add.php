<?php

use App\Legacy\LegacyContainer;

if (!allowed('comm_create')) {
    $errTab[] = 'Vous n\'avez pas les droits nécessaires pour cette operation';
}
$title_commission = stripslashes($_POST['title_commission']);
$uploads_dir = __DIR__.'/../../../public/ftp/transit/nouvellecommission';

// CHECKIN VARS
if (!isset($errTab) || 0 === count($errTab)) {
    if ('on' != $_POST['disable-bigfond'] && $_FILES['bigfond']['size'] < 5) {
        $errTab[] = 'Grande image non trouvée';
    }
    if ('on' != $_POST['disable-pictos'] && $_FILES['picto']['size'] < 5) {
        $errTab[] = 'Picto bleu non trouvé';
    }
    if ('on' != $_POST['disable-pictos'] && $_FILES['picto-light']['size'] < 5) {
        $errTab[] = 'Picto blanc non trouvé';
    }
    if ('on' != $_POST['disable-pictos'] && $_FILES['picto-dark']['size'] < 5) {
        $errTab[] = 'Picto sombre non trouvé';
    }
    if (strlen($title_commission) < 3) {
        $errTab[] = 'Titre de commission trop court';
    }
    if (strlen($title_commission) > 25) {
        $errTab[] = 'Titre de commission trop long ('.strlen($title_commission).')';
    }
}

// VIDAGE DU DOSSIER TRANSIT (evite les erreurs)
if (!isset($errTab) || 0 === count($errTab)) {
    if (file_exists($uploads_dir.'/bigfond.jpg')) {
        unlink($uploads_dir.'/bigfond.jpg');
    }
    if (file_exists($uploads_dir.'/picto.png')) {
        unlink($uploads_dir.'/picto.png');
    }
    if (file_exists($uploads_dir.'/picto-dark.png')) {
        unlink($uploads_dir.'/picto-dark.png');
    }
    if (file_exists($uploads_dir.'/picto-light.png')) {
        unlink($uploads_dir.'/picto-light.png');
    }
}

// GRANDE IMAGE
if ((!isset($errTab) || 0 === count($errTab)) && 'on' != $_POST['disable-bigfond']) {
    $tmp_name = $_FILES['bigfond']['tmp_name'];
    $name = $_FILES['bigfond']['name'];
    $type = $_FILES['bigfond']['type'];
    $size = $_FILES['bigfond']['size'];
    $error = $_FILES['bigfond']['error'];

    // erreur fichier php
    if ($error) {
        $errTab[] = "Erreur fichier grande image : $error";
    }

    // mes erreurs
    if (!isset($errTab) || 0 === count($errTab)) {
        $ext = strtolower(substr(strrchr($name, '.'), 1));
        if ('jpg' != $ext) {
            $errTab[] = 'La grande image doit être au format .jpg';
        }
        if ($size > 2400000) {
            $errTab[] = 'La grande image dépasse le poids maximum';
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        if (!move_uploaded_file($tmp_name, "$uploads_dir/bigfond.jpg")) {
            $errTab[] = 'Erreur à la copie de la grande image';
        }
    }
}

// PICTO 1
if ((!isset($errTab) || 0 === count($errTab)) && 'on' != $_POST['disable-pictos']) {
    $tmp_name = $_FILES['picto']['tmp_name'];
    $name = $_FILES['picto']['name'];
    $type = $_FILES['picto']['type'];
    $size = $_FILES['picto']['size'];
    $error = $_FILES['picto']['error'];

    // erreur fichier php
    if ($error) {
        $errTab[] = "Erreur picto bleu : $error";
    }

    // mes erreurs
    if (!isset($errTab) || 0 === count($errTab)) {
        $ext = strtolower(substr(strrchr($name, '.'), 1));
        if ('png' != $ext) {
            $errTab[] = 'Le picto bleu doit être au format .png';
        }
        if ($size > 160000) {
            $errTab[] = 'Le picto bleu dépasse le poids maximum (20ko)';
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        if (!move_uploaded_file($tmp_name, "$uploads_dir/picto.png")) {
            $errTab[] = 'Erreur à la copie du picto bleu';
        }
    }
}

// PICTO 2
if ((!isset($errTab) || 0 === count($errTab)) && 'on' != $_POST['disable-pictos']) {
    $tmp_name = $_FILES['picto-light']['tmp_name'];
    $name = $_FILES['picto-light']['name'];
    $type = $_FILES['picto-light']['type'];
    $size = $_FILES['picto-light']['size'];
    $error = $_FILES['picto-light']['error'];

    // erreur fichier php
    if ($error) {
        $errTab[] = "Erreur picto clair : $error";
    }

    // mes erreurs
    if (!isset($errTab) || 0 === count($errTab)) {
        $ext = strtolower(substr(strrchr($name, '.'), 1));
        if ('png' != $ext) {
            $errTab[] = 'Le picto clair doit être au format .png';
        }
        if ($size > 160000) {
            $errTab[] = 'Le picto clair dépasse le poids maximum (20ko)';
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        if (!move_uploaded_file($tmp_name, "$uploads_dir/picto-light.png")) {
            $errTab[] = 'Erreur à la copie du picto clair';
        }
    }
}

// PICTO 3
if ((!isset($errTab) || 0 === count($errTab)) && 'on' != $_POST['disable-pictos']) {
    $tmp_name = $_FILES['picto-dark']['tmp_name'];
    $name = $_FILES['picto-dark']['name'];
    $type = $_FILES['picto-dark']['type'];
    $size = $_FILES['picto-dark']['size'];
    $error = $_FILES['picto-dark']['error'];

    // erreur fichier php
    if ($error) {
        $errTab[] = "Erreur picto sombre : $error";
    }

    // mes erreurs
    if (!isset($errTab) || 0 === count($errTab)) {
        $ext = strtolower(substr(strrchr($name, '.'), 1));
        if ('png' != $ext) {
            $errTab[] = 'Le picto sombre doit être au format .png';
        }
        if ($size > 160000) {
            $errTab[] = 'Le picto sombre dépasse le poids maximum (20ko)';
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        if (!move_uploaded_file($tmp_name, "$uploads_dir/picto-dark.png")) {
            $errTab[] = 'Erreur à la copie du picto sombre';
        }
    }
}

$id_commission = null;

// SQL
if (!isset($errTab) || 0 === count($errTab)) {
    $code_commission = formater($title_commission, 3);
    $code_commission = LegacyContainer::get('legacy_mysqli_handler')->escapeString($code_commission);
    $title_commission = LegacyContainer::get('legacy_mysqli_handler')->escapeString($title_commission);

    // Le code doit être unique dans la base
    $passed = false;
    $suffixe = '';
    while (!$passed) {
        $req = "SELECT COUNT(id_commission) FROM caf_commission WHERE code_commission LIKE '$code_commission"."$suffixe'";
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $row = $result->fetch_row();
        if (0 == $row[0]) {
            $passed = true;
        } else {
            $suffixe = (int) $suffixe + 1;
        }
    }
    $code_commission .= $suffixe;

    // enregistrement
    $req = "INSERT INTO caf_commission(id_commission, ordre_commission, vis_commission, code_commission, title_commission)
                                                VALUES (NULL ,  '',  '0',  '$code_commission',  '$title_commission');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }
    $id_commission = LegacyContainer::get('legacy_mysqli_handler')->insertId();

    if (!$id_commission) {
        $errTab[] = 'Erreur SQL : id irrécupérable';
    }
}

// DÉPLACEMENT DES FICHIERS DANS LE DOSSIER FINAL
if (!isset($errTab) || 0 === count($errTab)) {
    $newDir = __DIR__.'/../../../public/ftp/commission/'.$id_commission;

    // création du dossier
    if (!file_exists($newDir)) {
        if (!mkdir($newDir) && !is_dir($newDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $newDir));
        }
    }

    // déplacement de chaque fichier bien envoyé
    if (file_exists("$uploads_dir/bigfond.jpg")) {
        copy("$uploads_dir/bigfond.jpg", "$newDir/bigfond.jpg");
        unlink("$uploads_dir/bigfond.jpg");
    }
    if (file_exists("$uploads_dir/picto.png")) {
        copy("$uploads_dir/picto.png", "$newDir/picto.png");
        unlink("$uploads_dir/picto.png");
    }
    if (file_exists("$uploads_dir/picto-dark.png")) {
        copy("$uploads_dir/picto-dark.png", "$newDir/picto-dark.png");
        unlink("$uploads_dir/picto-dark.png");
    }
    if (file_exists("$uploads_dir/picto-light.png")) {
        copy("$uploads_dir/picto-light.png", "$newDir/picto-light.png");
        unlink("$uploads_dir/picto-light.png");
    }
}

// REDIRECTION
if (!isset($errTab) || 0 === count($errTab)) {
    header('Location: /gestion-des-commissions.html');
}
