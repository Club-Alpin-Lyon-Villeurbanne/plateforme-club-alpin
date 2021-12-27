<?php

use App\Legacy\LegacyContainer;
use App\Utils\NicknameGenerator;

// vars
$id_evt = (int) ($_GET['id_evt']); // spécial : donné dans l'URL
$id_user = (int) ($_POST['id_user']);
$civ_user = stripslashes($_POST['civ_user']);
$cafnum_user = preg_replace('/\s+/', '', stripslashes($_POST['cafnum_user']));
$firstname_user = stripslashes($_POST['firstname_user']);
$lastname_user = stripslashes($_POST['lastname_user']);
$tel_user = stripslashes($_POST['tel_user']);
$tel2_user = stripslashes($_POST['tel2_user']);
$role_evt_join = stripslashes($_POST['role_evt_join']);

// suis-je encadrant sur cette sortie ?
$suis_encadrant = false;
$req = "SELECT COUNT(id_evt_join)
FROM caf_evt_join
WHERE evt_evt_join=$id_evt
AND user_evt_join = ".getUser()->getId()."
AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'coencadrant')
LIMIT 1";
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_encadrant = true;
}

// suis-je l'auteur de cette sortie ?
$suis_auteur = false;
$req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND user_evt = ".getUser()->getId().' LIMIT 1';
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_auteur = true;
}

// checks SQL : l'user doit être l'auteur *OU* avoir le droit de modifier toutes les inscriptions *OU* être encadrant sur la sortie
if (!allowed('evt_join_doall') && !allowed('evt_join_notme') && !$suis_encadrant && !$suis_auteur) {
    $errTab[] = 'Opération interdite : Il semble que vous ne soyez pas autorisé à ajouter des inscrits';
}

// checks :
if (!$id_evt) {
    $errTab[] = 'ID event manquant';
}
if (!$civ_user) {
    $errTab[] = 'Civilité manquante';
}
if (!$cafnum_user) {
    $errTab[] = "Numéro d'adhérent manquant ou invalide";
}
if (!$firstname_user) {
    $errTab[] = 'Merci de renseigner le champ prénom';
}
if (!$lastname_user) {
    $errTab[] = 'Merci de renseigner le champ nom';
}
if (!$role_evt_join) {
    $errTab[] = 'Merci de renseigner le champ <i>rôle</i>';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // si pas d'ID user spécifié, on crée ce nomade
    if (!$id_user) {
        // securite
        $civ_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($civ_user);
        $cafnum_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($cafnum_user);
        $firstname_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($firstname_user);
        $lastname_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lastname_user);
        $nickname_user = NicknameGenerator::generateNickname($firstname_user, $lastname_user);
        $tel_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tel_user);
        $tel2_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tel2_user);

        $req = "INSERT INTO caf_user(mdp_user, cafnum_user, firstname_user, lastname_user, nickname_user, created_user, birthday_user, tel_user, tel2_user, adresse_user, cp_user, ville_user, pays_user, civ_user, moreinfo_user, auth_contact_user, valid_user ,cookietoken_user, manuel_user, nomade_user, nomade_parent_user, cafnum_parent_user, doit_renouveler_user, alerte_renouveler_user)
                        VALUES ('',  'N_$cafnum_user',  '$firstname_user',  '$lastname_user',  '$nickname_user',  '".time()."',  NULL,  '$tel_user',  '$tel2_user',  '',  '',  '',  '',  '$civ_user',  '',  'none',  '1',  '',  '0',  '1',  '".getUser()->getId()."', '', 0, 0)";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        } else {
            $id_user = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
    }

    // a ce stade, on doit avoir l'ID de l'user
    if (!$id_user) {
        $errTab[] = "Erreur de génération de l'ID user";
    }

    // plus qu'à joindre cet user à l'evt
    if (!isset($errTab) || 0 === count($errTab)) {
        // securite

        if ($_POST['is_cb']) {
            $is_cb = $_POST['is_cb'];
            if ('1' == $is_cb) {
                $is_cb = 1;
            } elseif ('0' == $is_cb) {
                $is_cb = 0;
            } else {
                $is_cb = 'NULL';
            }
        } else {
            $is_cb = 'NULL';
        }

        if ($_POST['is_restaurant']) {
            $is_restaurant = $_POST['is_restaurant'];
            if ('1' == $is_restaurant) {
                $is_restaurant = 1;
            } elseif ('0' == $is_restaurant) {
                $is_restaurant = 0;
            } else {
                $is_restaurant = 'NULL';
            }
        } else {
            $is_restaurant = 'NULL';
        }

        if ($_POST['id_bus_lieu_destination']) {
            $id_bus_lieu_destination = $_POST['id_bus_lieu_destination'];
            $is_covoiturage = '0';
            if ('-1' == $id_bus_lieu_destination) {
                $id_bus_lieu_destination = '0';
                $is_covoiturage = '1';
            }
        } else {
            $id_bus_lieu_destination = 'NULL';
            $is_covoiturage = 'NULL';
        }

        // on vérifie ls places dans les bus
        if ($_POST['id_destination']) {
            $id_destination = $_POST['id_destination'];

            // Vérifier les places dans le bus sélectionné
            if ($id_bus_lieu_destination > 0) { // sinon c'est du covoiturage
                $nbp = nb_places_restante_bus_ramassage($id_bus_lieu_destination);
                if ($nbp <= 0) {
                    $errTab[] = 'Ce bus est désormais plein. Merci de choisir un autre lieu de ramassage pour '.$civ_user.' '.$lastname_user.' '.$firstname_user.' et suivants.';
                }
            }
        } else {
            $id_destination = 'NULL';
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            $role_evt_join = LegacyContainer::get('legacy_mysqli_handler')->escapeString($role_evt_join);

            // attention : status_evt_join est à 0 ici par défaut
            $status_evt_join = 0;
            if ($suis_encadrant || $suis_auteur) {
                $status_evt_join = 1;
            }

            $req = "INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join, lastchange_when_evt_join, lastchange_who_evt_join, is_cb, is_restaurant, id_bus_lieu_destination, id_destination, is_covoiturage, affiliant_user_join)
                                    VALUES($status_evt_join, 		'$id_evt',  '$id_user',  	'$role_evt_join', ".time().', 		'.time().', 			'.getUser()->getId().",
                        $is_cb, $is_restaurant, $id_bus_lieu_destination, $id_destination, $is_covoiturage, null);";
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
        }
    }
}
