<?php

use App\Legacy\LegacyContainer;
use App\Utils\NicknameGenerator;

// vars
$id_evt = (int) $_GET['id_evt']; // spécial : donné dans l'URL
$id_user = (int) $_POST['id_user'];
$civ_user = stripslashes($_POST['civ_user']);
$cafnum_user = preg_replace('/\s+/', '', stripslashes($_POST['cafnum_user']));
$firstname_user = ucfirst(stripslashes($_POST['firstname_user']));
$lastname_user = strtoupper(stripslashes($_POST['lastname_user']));
$tel_user = stripslashes($_POST['tel_user']);
$tel2_user = stripslashes($_POST['tel2_user']);
$email_user = stripslashes($_POST['email_user']);
$role_evt_join = stripslashes($_POST['role_evt_join']);
$birthday_user = trim(stripslashes($_POST['birthday_user']));

// suis-je encadrant sur cette sortie ?
$suis_encadrant = false;
$stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT COUNT(id_evt_join)
FROM caf_evt_join
WHERE evt_evt_join = ?
AND user_evt_join = ?
AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'stagiaire' OR role_evt_join LIKE 'coencadrant')
LIMIT 1");
$stmt->bind_param('ii', $id_evt, getUser()->getId());
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_encadrant = true;
}
$stmt->close();

// suis-je l'auteur de cette sortie ?
$suis_auteur = false;
$stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt = ? AND user_evt = ? LIMIT 1');
$stmt->bind_param('ii', $id_evt, getUser()->getId());
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_auteur = true;
}
$stmt->close();

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
} elseif (!$id_user) {
    $reqmail = 'SELECT COUNT(*) FROM caf_user WHERE cafnum_user = ?';
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($reqmail);
    $stmt->bind_param('s', $cafnum_user);
    $stmt->execute();
    $resultmail = $stmt->get_result();
    $rowmail = $resultmail->fetch_row();
    if ($rowmail[0] > 0) {
        $errTab[] = "Le numéro d'adhérent existe déja sur le site";
    }
    $stmt->close();
}
if (!$firstname_user) {
    $errTab[] = 'Merci de renseigner le champ prénom';
}
if (!$lastname_user) {
    $errTab[] = 'Merci de renseigner le champ nom';
}
if ($email_user && !filter_var($email_user, \FILTER_VALIDATE_EMAIL)) {
    $errTab[] = "L'adresse email est invalide";
} elseif (!$id_user) {
    $reqmail = 'SELECT COUNT(*) FROM caf_user WHERE email_user = ?';
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($reqmail);
    $stmt->bind_param('s', $email_user);
    $stmt->execute();
    $resultmail = $stmt->get_result();
    $rowmail = $resultmail->fetch_row();
    if ($rowmail[0] > 0) {
        $errTab[] = "L'adresse email existe déja sur le site";
    }
    $stmt->close();
}
if (!$role_evt_join) {
    $errTab[] = 'Merci de renseigner le champ <i>rôle</i>';
}
// date de naissance :
if (!preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $birthday_user)) {
    $errTab[] = 'La date de naissance doit être au format jj/mm/aaaa.';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $tab = explode('/', $birthday_user);
    $birthday_user = mktime(0, 0, 0, $tab[1], $tab[0], $tab[2]);

    // si pas d'ID user spécifié, on crée ce nomade
    if (!$id_user) {
        $nickname_user = NicknameGenerator::generateNickname($firstname_user, $lastname_user);

        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO caf_user(
            email_user, mdp_user, cafnum_user, firstname_user, lastname_user, nickname_user, created_user, birthday_user, tel_user, tel2_user, adresse_user, cp_user, ville_user, pays_user, civ_user, moreinfo_user, auth_contact_user, valid_user, cookietoken_user, manuel_user, nomade_user, nomade_parent_user, cafnum_parent_user, doit_renouveler_user, alerte_renouveler_user
        ) VALUES (
            ?,
            \'\',
            ?, ?, ?, ?, ?, ?, ?, ?, \'\', \'\', \'\', \'\', ?, \'\', \'none\', 1, \'\', 0, 1, ?, NULL, 0, 0
        )');
        $current_time = time();
        $parent_id = getUser()->getId();
        $stmt->bind_param('sssssiisssi', $email_user, $cafnum_user, $firstname_user, $lastname_user, $nickname_user, $current_time, $birthday_user, $tel_user, $tel2_user, $civ_user, $parent_id);
        if (!$stmt->execute()) {
            $errTab[] = 'Erreur SQL';
        } else {
            $id_user = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
        $stmt->close();
    } else {
        // sinon, on met à jour les infos de l'user
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_user SET
            email_user = ?,
            birthday_user = ?,
            tel_user = ?,
            tel2_user = ?
            WHERE id_user = ?
            LIMIT 1');
        $stmt->bind_param('sissi', $email_user, $birthday_user, $tel_user, $tel2_user, $id_user);
        if (!$stmt->execute()) {
            $errTab[] = 'Erreur SQL';
        }
        $stmt->close();
    }

    // a ce stade, on doit avoir l'ID de l'user
    if (!$id_user) {
        $errTab[] = "Erreur de génération de l'ID user";
    }

    // plus qu'à joindre cet user à l'evt
    if (!isset($errTab) || 0 === count($errTab)) {
        // securite

        $is_covoiturage = 'NULL';

        if (!isset($errTab) || 0 === count($errTab)) {
            // attention : status_evt_join est à 0 ici par défaut
            $status_evt_join = 0;
            if ($suis_encadrant || $suis_auteur) {
                $status_evt_join = 1;
            }

            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join, lastchange_when_evt_join, lastchange_who_evt_join, is_covoiturage, affiliant_user_join) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)');
            $current_time = time();
            $who = getUser()->getId();
            $stmt->bind_param('iiisiii', $status_evt_join, $id_evt, $id_user, $role_evt_join, $current_time, $current_time, $who);
            if (!$stmt->execute()) {
                $errTab[] = 'Erreur SQL';
            }
            $stmt->close();
        }
    }
}
