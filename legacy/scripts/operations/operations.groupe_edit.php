<?php

use App\Legacy\LegacyContainer;

if (!allowed('comm_groupe_edit')) {
    $errTab[] = 'Vous n\'avez pas les droits nécessaires pour cette operation de gestion de groupe';
}

$id_commission = (int) $_GET['id_commission'];

// CHECKIN VARS
if (!isset($errTab) || 0 === count($errTab)) {
    $new_groupe = $_POST['new_groupe'];
    if (isset($new_groupe) && is_array($new_groupe)) {
        foreach ($new_groupe as $groupe) {
            $id_comm = (int) $groupe['id_commission'];
            $niveau_technique = (int) $groupe['niveau_technique'];
            $niveau_physique = (int) $groupe['niveau_physique'];
            $nom = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($groupe['nom']));
            $description = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($groupe['description']));
            if (empty($nom)) {
                $errTab[] = 'Le nom du groupe est obligatoire';
            }
            if ($id_comm != $id_commission) {
                $errTab[] = 'Erreur de commission';
            }

            if (!isset($errTab) || 0 === count($errTab)) {
                $req =
                "INSERT INTO `caf_groupe` (`id_commission`, `nom`, `description`, `niveau_physique`, `niveau_technique`, `actif`)
                    VALUES ('".$id_comm."', '".$nom."', '".$description."', '".$niveau_physique."', '".$niveau_technique."', '1');";
                if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                    $errTab[] = 'Erreur SQL insertion groupe';
                }
            }
        }
    }

    $groupes = $_POST['groupe'];
    if (isset($groupes) && is_array($groupes)) {
        foreach ($groupes as $groupe) {
            $id_groupe = (int) $groupe['id'];
            $niveau_technique = (int) $groupe['niveau_technique'];
            $niveau_physique = (int) $groupe['niveau_physique'];
            $actif = (int) $groupe['actif'];
            $nom = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($groupe['nom']));
            $description = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($groupe['description']));
            if (empty($groupe['nom'])) {
                $errTab[] = 'Le nom du groupe est obligatoire';
            }

            if (!isset($errTab) || 0 === count($errTab)) {
                $need_comma = false;
                $req = 'UPDATE `caf_groupe` SET ';
                if ($groupe['nom']) {
                    $req .= "`nom` = '".$nom."' ";
                    $need_comma = true;
                }
                if ($groupe['description']) {
                    $req .= $need_comma ? ' , ' : '';
                    $req .= " `description` = '".$description."' ";
                    $need_comma = true;
                }
                if (isset($groupe['niveau_technique'])) {
                    $req .= $need_comma ? ' , ' : '';
                    $req .= "  `niveau_technique` = '".$niveau_technique."' ";
                    $need_comma = true;
                }
                if (isset($groupe['niveau_physique'])) {
                    $req .= $need_comma ? ' , ' : '';
                    $req .= "  `niveau_physique` = '".$niveau_physique."' ";
                    $need_comma = true;
                }
                if (isset($groupe['actif'])) {
                    $req .= $need_comma ? ' , ' : '';
                    $req .= "  `actif` = '".$actif."' ";
                    $need_comma = true;
                }
                $req .= 'WHERE `id` = '.$id_groupe;

                if (isset($groupe['delete']) && 'on' == $groupe['delete']) {
                    $req = 'DELETE FROM `caf_groupe` WHERE `id` = '.$id_groupe;
                }

                if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                    $errTab[] = 'Erreur SQL update / delete groupe';
                }
            }
        }
    }
}
