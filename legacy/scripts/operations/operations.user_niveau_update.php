<?php

global $kernel;

// Utilisateur connecté ?
if (!user()) {
    $errTab[] = "Vous avez été déconnecté. L'opération n'a pas été effectuée.";
}

// mise à jour des informations de niveau
// il devrait y avoir une vérification de l'autorisation à modifier le niveau utilisateur

if (!isset($errTab) || 0 === count($errTab)) {
    if (isset($_POST['new_niveau']) && is_array($_POST['new_niveau'])) {
        foreach ($_POST['new_niveau'] as $niveau) {
            $id_user = (int) ($niveau['id_user']);
            $id_commission = (int) ($niveau['id_commission']);
            $niveau_technique = (int) ($niveau['niveau_technique']);
            $niveau_physique = (int) ($niveau['niveau_physique']);
            $commentaire = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString(trim(stripslashes($niveau['commentaire'])));
            if (empty($commentaire)) {
                $commentaire = null;
            }

            if ((!isset($errTab) || 0 === count($errTab)) && (null !== $commentaire || $niveau_technique > 0 || $niveau_physique > 0)) {
                $req = "INSERT INTO `caf_user_niveau` (`id`, `id_user`, `id_commission`, `niveau_technique`, `niveau_physique`, `commentaire`) VALUES (NULL, '".$id_user."', '".$id_commission."', '".$niveau_technique."', '".$niveau_physique."', ";
                if (null === $commentaire) {
                    $req .= 'NULL';
                } else {
                    $req .= "'".$commentaire."' ";
                }
                $req .= ');';
                if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
                    $errTab[] = 'Erreur SQL lors Insertion note pour commission '.$id_commission.' et utilisateur '.$id_user;
                }
            }
        }
    }

    if (isset($_POST['niveau']) && is_array($_POST['niveau'])) {
        foreach ($_POST['niveau'] as $id_niveau => $niveau) {
            $id = trim(stripslashes($niveau['id']));
            if ($id_niveau != $id) {
                $errTab[] = 'Id niveau ne correspond pas';
            }

            $niveau_technique = (int) ($niveau['niveau_technique']);
            $niveau_physique = (int) ($niveau['niveau_physique']);
            $commentaire = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString(trim(stripslashes($niveau['commentaire'])));
            if (empty($commentaire)) {
                $commentaire = null;
            }

            if (!isset($errTab) || 0 === count($errTab)) {
                $req = "UPDATE `caf_user_niveau` SET `niveau_technique` = '".$niveau_technique."', `niveau_physique` = '".$niveau_physique."', `commentaire` = ";
                if (null === $commentaire) {
                    $req .= 'NULL';
                } else {
                    $req .= "'".$commentaire."' ";
                }
                $req .= ' WHERE `caf_user_niveau`.`id` = '.$id.';';
                if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
                    $errTab[] = 'Erreur SQL';
                }
            }
        }
    }
}
