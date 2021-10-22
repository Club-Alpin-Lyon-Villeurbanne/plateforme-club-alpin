<?php

    if (!allowed('comm_edit')) {
        $errTab[] = 'Vous n\'avez pas les droits nécessaires pour cette operation';
    }
    $title_commission = stripslashes($_POST['title_commission']);
    $id_commission = (int) ($_GET['id_commission']);

    // CHECKIN VARS
    if (!count($errTab)) {
        $uploads_dir = 'ftp/commission/'.$id_commission;

        if (strlen($title_commission) < 3) {
            $errTab[] = 'Titre de commission trop court';
        }
        if (strlen($title_commission) > 25) {
            $errTab[] = 'Titre de commission trop long';
        }
        if (!$id_commission) {
            $errTab[] = 'ID invalide';
        }
        // if(!file_exists($uploads_dir)) 												$errTab[]='Dossier introuvable';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir);
        }
    }

    // VIDAGE DU DOSSIER TRANSIT (evite les erreurs)
    if (!count($errTab)) {
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
    if (!count($errTab) && $_FILES['bigfond']['size'] > 5) {
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
        if (!count($errTab)) {
            $ext = strtolower(substr(strrchr($name, '.'), 1));
            if ('jpg' != $ext) {
                $errTab[] = 'La grande image doit être au format .jpg';
            }
            if ($size > 2400000) {
                $errTab[] = 'La grande image dépasse le poids maximum';
            }
        }

        if (!count($errTab)) {
            if (!move_uploaded_file($tmp_name, "$uploads_dir/bigfond.jpg")) {
                $errTab[] = 'Erreur à la copie de la grande image';
            }
        }
    }

    // PICTO 1
    if (!count($errTab) && $_FILES['picto']['size'] > 5) {
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
        if (!count($errTab)) {
            $ext = strtolower(substr(strrchr($name, '.'), 1));
            if ('png' != $ext) {
                $errTab[] = 'Le picto bleu doit être au format .png';
            }
            if ($size > 160000) {
                $errTab[] = 'Le picto bleu dépasse le poids maximum (20ko)';
            }
        }

        if (!count($errTab)) {
            if (!move_uploaded_file($tmp_name, "$uploads_dir/picto.png")) {
                $errTab[] = 'Erreur à la copie du picto bleu';
            }
        }
    }

    // PICTO 2
    if (!count($errTab) && $_FILES['picto-light']['size'] > 5) {
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
        if (!count($errTab)) {
            $ext = strtolower(substr(strrchr($name, '.'), 1));
            if ('png' != $ext) {
                $errTab[] = 'Le picto clair doit être au format .png';
            }
            if ($size > 160000) {
                $errTab[] = 'Le picto clair dépasse le poids maximum (20ko)';
            }
        }

        if (!count($errTab)) {
            if (!move_uploaded_file($tmp_name, "$uploads_dir/picto-light.png")) {
                $errTab[] = 'Erreur à la copie du picto clair';
            }
        }
    }

    // PICTO 3
    if (!count($errTab) && $_FILES['picto-dark']['size'] > 5) {
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
        if (!count($errTab)) {
            $ext = strtolower(substr(strrchr($name, '.'), 1));
            if ('png' != $ext) {
                $errTab[] = 'Le picto sombre doit être au format .png';
            }
            if ($size > 160000) {
                $errTab[] = 'Le picto sombre dépasse le poids maximum (20ko)';
            }
        }

        if (!count($errTab)) {
            if (!move_uploaded_file($tmp_name, "$uploads_dir/picto-dark.png")) {
                $errTab[] = 'Erreur à la copie du picto sombre';
            }
        }
    }

    // SQL
    if (!count($errTab)) {
        include SCRIPTS.'connect_mysqli.php';
        $title_commission = $mysqli->real_escape_string($title_commission);

        // enregistrement
        $req = "UPDATE caf_commission SET title_commission = '$title_commission' WHERE id_commission =$id_commission";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
        $mysqli->close;

        if (!$id_commission) {
            $errTab[] = 'Erreur SQL : id irrécupérable';
        }
    }
