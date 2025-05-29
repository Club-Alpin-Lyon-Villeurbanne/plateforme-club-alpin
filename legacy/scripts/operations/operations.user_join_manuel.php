<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$errTabMail = [];

// vars
$id_evt = (int) $_GET['id_evt']; // spécial : donné dans l'URL

// suis-je encadrant sur cette sortie ?
$suis_encadrant = false;
$stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT COUNT(id_evt_join) FROM caf_evt_join WHERE evt_evt_join = ? AND user_evt_join = ? AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'stagiaire' OR role_evt_join LIKE 'coencadrant') LIMIT 1");
$user_id = getUser()->getId();
$stmt->bind_param("ii", $id_evt, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_encadrant = true;
}
$stmt->close();

// suis-je l'auteur de cette sortie ?
$suis_auteur = false;
$stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt = ? AND user_evt = ? LIMIT 1");
$stmt->bind_param("ii", $id_evt, $user_id);
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
if (!is_array($_POST['id_user'])) {
    $errTab[] = 'Tableau des ID manquant';
} else {
    // if(sizeof($_POST['id_user']) != sizeof($_POST['civ_user'])) $errTab[]="Erreur de correspondance de la var civ_user";
    // if(sizeof($_POST['id_user']) != sizeof($_POST['lastname_user'])) $errTab[]="Erreur de correspondance de la var lastname_user";
    // if(sizeof($_POST['id_user']) != sizeof($_POST['firstname_user'])) $errTab[]="Erreur de correspondance de la var firstname_user";
    if (count($_POST['id_user']) != count($_POST['role_evt_join'])) {
        $errTab[] = 'Erreur de correspondance de la var role_evt_join';
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    // verification de la validité de la sortie
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt = ? AND status_evt != 1 LIMIT 1");
    $stmt->bind_param("i", $id_evt);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    if ($row[0]) {
        $errTab[] = 'Cette sortie ne semble pas publiée, les inscriptions sont impossible';
    }
    $stmt->close();

    // pour chaque id donné
    foreach ($_POST['id_user'] as $i => $user) {
        $id_user = (int) $_POST['id_user'][$i];
        $role_evt_join = stripslashes($_POST['role_evt_join'][$i]);

        $is_covoiturage = 'NULL';

        // si pas de pb, intégration

        if (!isset($errTab) || 0 === count($errTab)) {
            // attention : status_evt_join est à 1 ici par défaut
            $status_evt_join = 0;
            if ($suis_encadrant || $suis_auteur) {
                $status_evt_join = 1;
            }

            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join, lastchange_when_evt_join, lastchange_who_evt_join, is_covoiturage, affiliant_user_join) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)");
            $current_time = time();
            $stmt->bind_param("iiisiiis", $status_evt_join, $id_evt, $id_user, $role_evt_join, $current_time, $current_time, $user_id, $is_covoiturage);
            if (!$stmt->execute()) {
                $errTab[] = 'Erreur SQL';
            } else {
                unset($_POST['id_user'][$i]);
                unset($_POST['civ_user'][$i]);
                unset($_POST['lastname_user'][$i]);
                unset($_POST['firstname_user'][$i]);
                unset($_POST['nickname_user'][$i]);
                unset($_POST['role_evt_join'][$i]);
            }
            $stmt->close();
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            // ENVOI DU MAIL

            // recup de son email & nom
            $toMail = '';
            $toName = '';
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT email_user, firstname_user, lastname_user, civ_user FROM caf_user WHERE id_user = ? LIMIT 1");
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $toMail = $row['email_user'];
                $toName = ucfirst($row['firstname_user']);
            }
            $stmt->close();
            if (!isMail($toMail)) {
                $errTabMail[] = "Les coordonnées du contact sont erronées (l'inscription est réalisée quand même)";
            }

            // recup infos evt
            $evtUrl = '';
            $evtName = '';
            $evtDate = '';
            $commissionTitle = '';
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT id_evt, code_evt, titre_evt, tsp_evt, title_commission FROM caf_evt AS e INNER JOIN caf_commission AS c ON (c.id_commission = e.commission_evt) WHERE id_evt = ? LIMIT 1");
            $stmt->bind_param("i", $id_evt);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $evtUrl = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $row['code_evt'] . '-' . $row['id_evt'] . '.html';
                $evtName = $row['titre_evt'];
                $evtDate = date('d/m/Y', $row['tsp_evt']);
                $commissionTitle = $row['title_commission'];
            }
            $stmt->close();

            if (0 === count($errTabMail)) {
                LegacyContainer::get('legacy_mailer')->send($toMail, 'transactional/sortie-inscription', [
                    'role' => 'manuel' === $role_evt_join ? null : $role_evt_join,
                    'event_name' => $evtName,
                    'event_url' => $evtUrl,
                    'event_date' => $evtDate,
                    'commission' => $commissionTitle,
                ]);
            }
        }
    }
}
if (count($errTabMail)) {
    $errTab = array_merge($errTabMail, isset($errTab) ? $errTab : []);
}

if (!isset($errTab) || 0 === count($errTab)) {
    $_POST['id_user'] = [0];
    $_POST['result'] = 'success';
}
