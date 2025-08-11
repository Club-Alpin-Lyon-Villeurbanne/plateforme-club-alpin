<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$is_covoiturage = $evtUrl = $evtName = $inscrits = $evtDate = $commissionTitle = null;
$auto_accept = false;

// Filiations
if (isset($_POST['filiations']) && 'on' == $_POST['filiations']) {
    $filiations = true;
} else {
    $filiations = false;
}

$idUsersFiliations = !empty($_POST['id_user_filiation']) ? array_map('intval', $_POST['id_user_filiation']) : [];

$joinMessage = $_POST['message'];

// Evenement défini et utilisateur aussi
$id_evt = (int) $_POST['id_evt'];
$id_user = getUser()->getId();
if (!$id_user || !$id_evt) {
    $errTab[] = 'Erreur de données';
}

// CGUs
if (!isset($_POST['confirm']) || 'on' != $_POST['confirm']) {
    $errTab[] = "Merci de cocher la case &laquo; J'ai lu les conditions...&raquo;";
}

if (!isset($errTab) || 0 === count($errTab)) {
    // Bénévole
    if (isset($_POST['jeveuxetrebenevole']) && 'on' == $_POST['jeveuxetrebenevole']) {
        $role_evt_join = 'benevole';
    } else {
        $role_evt_join = 'inscrit';
    }

    // si filiations : création du tableau des joints et vérifications
    if ($filiations) {
        if (!count($idUsersFiliations)) {
            $errTab[] = 'Merci de choisir au moins une personne à inscrire';
        }
        // pour chaque id envoyé
        foreach ($idUsersFiliations as $id_user_tmp) {
            // vérification que c'est bien mon affilié
            // sauf moi-meme
            if ($id_user_tmp != getUser()->getId()) {
                $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE cafnum_parent_user = ? AND id_user = ?');
                $stmt->bind_param('si', getUser()->getCafnum(), $id_user_tmp);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_row();
                $stmt->close();
                if (!$row[0]) {
                    $errTab[] = "ID '" . (int) $id_user_tmp . "' invalide pour l'inscription d'un adhérent affilié";
                }
            }
        }
    }

    // verification de la validité de la sortie
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt = ? AND status_evt != 1 LIMIT 1');
    $stmt->bind_param('i', $id_evt);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    if ($row[0]) {
        $errTab[] = 'Cette sortie ne semble pas publiée, les inscriptions sont impossible';
    }

    // verification du timing de la sortie
    $current_time = time();
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt = ? AND tsp_evt < ? LIMIT 1');
    $stmt->bind_param('ii', $id_evt, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    if ($row[0]) {
        $errTab[] = 'Cette sortie a deja demarrée';
    }

    // verification du timing de la sortie : inscriptions
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt = ? AND join_start_evt > ? LIMIT 1');
    $stmt->bind_param('ii', $id_evt, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    if ($row[0]) {
        $errTab[] = 'Les inscriptions ne sont pas encore ouvertes';
    }

    // Doit on faire une mise à jour ?
    $update = [];

    // verification de l'existence de cette demande
    if (!$filiations) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_evt_join) FROM caf_evt_join WHERE evt_evt_join = ? AND user_evt_join = ? LIMIT 1');
        $stmt->bind_param('ii', $id_evt, $id_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        if ($row[0]) {
            // $errTab[]="Vous semblez déjà être pré-inscrit à cette sortie.";
            $update[] = $id_user;
        }
    }
    // pour les inscriptions d'affilié
    else {
        foreach ($idUsersFiliations as $id_user_tmp) {
            $id_user_tmp = (int) $id_user_tmp;
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, lastname_user, firstname_user, civ_user
            FROM caf_evt_join, caf_user
            WHERE evt_evt_join = ?
            AND user_evt_join = id_user
            AND id_user = ?
            LIMIT 1');
            $stmt->bind_param('ii', $id_evt, $id_user_tmp);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                // $errTab[]=$row['firstname_user']." ".$row['lastname_user']." semble déjà être pré-inscrit(e) à cette sortie.";
                $update[] = $id_user_tmp;
            }
            $stmt->close();
        }
    }

    // SI PAS DE PB, INTÉGRATION BDD
    if (!isset($errTab) || 0 === count($errTab)) {
        $is_covoiturage = null;
        $current_timestamp = time();
        $success = true;

        // si on accepte les demandes automatiquement
        $status_evt_join = 0;
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT auto_accept, ngens_max_evt FROM caf_evt WHERE id_evt = ? LIMIT 1');
        $stmt->bind_param('i', $id_evt);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        if (1 === $row[0]) {
            // Si auto_accept est activé, vérifier qu'on n'a pas atteint la limite
            $ngens_max = $row[1];
            if ($ngens_max && $ngens_max > 0) {
                // Compter le nombre actuel de participants acceptés
                $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_evt_join) FROM caf_evt_join WHERE evt_evt_join = ? AND status_evt_join = 1');
                $stmt->bind_param('i', $id_evt);
                $stmt->execute();
                $result = $stmt->get_result();
                $count_row = $result->fetch_row();
                $stmt->close();
                $current_participants = $count_row[0];

                // Vérifier si on peut accepter au moins une nouvelle inscription
                if ($current_participants < $ngens_max) {
                    $status_evt_join = 1;
                    $auto_accept = true;
                }
            // Si on a atteint la limite, ne pas accepter automatiquement
            } else {
                // Si pas de limite définie, accepter automatiquement
                $status_evt_join = 1;
                $auto_accept = true;
            }
        }

        $evt = get_evt($id_evt);

        // normal
        if (!$filiations) {
            if (!$update) {
                $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join, is_covoiturage, affiliant_user_join, lastchange_when_evt_join, lastchange_who_evt_join)
                          VALUES(?, ?, ?, ?, ?, ?, null, null, null)');
                $stmt->bind_param('iiisii', $status_evt_join, $id_evt, $id_user, $role_evt_join, $current_timestamp, $is_covoiturage);
                $success = $stmt->execute();
                $stmt->close();
            } elseif (in_array($id_user, $update, true)) {
                $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE `caf_evt_join`
                            SET
                                `is_covoiturage` = ?
                            WHERE
                                `user_evt_join` = ? AND evt_evt_join = ?');
                $stmt->bind_param('iii', $is_covoiturage, $id_user, $id_evt);
                $success = $stmt->execute();
                $stmt->close();
            }
            if (!$success) {
                $errTab[] = 'Erreur SQL';
            }
        }
        // filiations
        else {
            foreach ($idUsersFiliations as $id_user_tmp) {
                if (!$update || !in_array($id_user_tmp, $update, true)) {
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, affiliant_user_join, role_evt_join, tsp_evt_join, is_covoiturage)
                              VALUES(?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('iiiiisi', $status_evt_join, $id_evt, $id_user_tmp, $id_user, $role_evt_join, $current_timestamp, $is_covoiturage);
                    $success = $stmt->execute();
                    $stmt->close();
                } elseif (in_array($id_user_tmp, $update, true)) {
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE `caf_evt_join`
                            SET
                                `is_covoiturage` = ?
                            WHERE
                                `user_evt_join` = ? AND evt_evt_join = ?');
                    $stmt->bind_param('iii', $is_covoiturage, $id_user_tmp, $id_evt);
                    $success = $stmt->execute();
                    $stmt->close();
                }
                if (!$success) {
                    $errTab[] = 'Erreur SQL';
                }
            }
        }
    }

    // E-MAIL À L'ORGANISATEUR ET AUX ENCADRANTS
    if (!isset($errTab) || 0 === count($errTab)) {
        $destinataires = [];

        // créateur de sortie (on utilise les ID comme clé pour éviter le doublon d'email créateur de sortie + encadreant de sortie)
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, email_user, nickname_user, firstname_user, lastname_user, civ_user '
        . 'FROM caf_user, caf_evt '
        . 'WHERE id_user = user_evt '
        . 'AND id_evt = ? '
        . 'LIMIT 1');
        $stmt->bind_param('i', $id_evt);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $destinataires['' . $row['id_user']] = $row;
        }
        $stmt->close();

        // encadrants (on utilise les ID comme clé pour éviter le doublon d'email créateur de sortie + encadreant de sortie)
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, email_user, nickname_user, firstname_user, lastname_user, civ_user, role_evt_join '
        . 'FROM caf_user, caf_evt_join '
        . 'WHERE id_user = user_evt_join '
        . 'AND evt_evt_join = ? '
        . 'AND status_evt_join = 1 '
        . "AND (role_evt_join = 'encadrant' OR role_evt_join = 'stagiaire' OR role_evt_join = 'coencadrant')");
        $stmt->bind_param('i', $id_evt);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $destinataires['' . $row['id_user']] = $row;
        }
        $stmt->close();

        // infos sur la sortie
        $evt = [];
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_evt, code_evt, titre_evt, tsp_evt, title_commission '
        . 'FROM caf_evt AS e '
        . 'INNER JOIN caf_commission AS c ON (c.id_commission = e.commission_evt) '
        . 'WHERE id_evt = ? '
        . 'LIMIT 1');
        $stmt->bind_param('i', $id_evt);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $evt = $row;
        }
        $stmt->close();

        $evtUrl = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $evt['code_evt'] . '-' . $evt['id_evt'] . '.html';
        $evtName = $evt['titre_evt'];
        $evtDate = date('d/m/Y', $evt['tsp_evt']);
        $commissionTitle = $evt['title_commission'];

        // infos sur ce nouvel inscrit
        $inscrits = [];
        if ($filiations) {
            // Pour les filiations, utiliser une requête préparée avec des placeholders dynamiques
            $placeholders = str_repeat('?,', count($idUsersFiliations) - 1) . '?';
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT id_user, email_user, nickname_user, firstname_user, lastname_user, civ_user, birthday_user
                FROM caf_user
                WHERE id_user IN ($placeholders)
                LIMIT 100");
            $stmt->bind_param(str_repeat('i', count($idUsersFiliations)), ...$idUsersFiliations);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $inscrits[] = $row;
            }
            $stmt->close();
        } else {
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, email_user, nickname_user, firstname_user, lastname_user, civ_user, birthday_user
                FROM caf_user
                WHERE id_user = ?
                LIMIT 1');
            $stmt->bind_param('i', $id_user);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $inscrits[] = $row;
            }
            $stmt->close();
        }

        foreach ($destinataires as $id_destinataire => $destinataire) {
            LegacyContainer::get('legacy_mailer')->send($destinataire['email_user'], 'transactional/sortie-demande-inscription', [
                'role' => $role_evt_join,
                'event_name' => $evtName,
                'event_url' => $evtUrl,
                'event_date' => $evtDate,
                'auto_accept' => $auto_accept,
                'commission' => $commissionTitle,
                'inscrits' => array_map(function ($cetinscrit) {
                    return [
                        'firstname' => ucfirst($cetinscrit['firstname_user']),
                        'lastname' => strtoupper($cetinscrit['lastname_user']),
                        'nickname' => $cetinscrit['nickname_user'],
                        'email' => $cetinscrit['email_user'],
                        'profile_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-full/' . $cetinscrit['id_user'] . '.html',
                    ];
                }, $inscrits),
                'firstname' => ucfirst(getUser()->getFirstname()),
                'lastname' => strtoupper(getUser()->getLastname()),
                'nickname' => getUser()->getNickname(),
                'message' => $joinMessage,
                'covoiturage' => $is_covoiturage,
                'dest_role' => array_key_exists('role_evt_join', $destinataire) ? $destinataire['role_evt_join'] : 'l\'auteur',
            ], [], null, getUser()->getEmail());
        }
    }

    // E-MAIL AU PRE-INSCRIT
    if (!isset($errTab) || 0 === count($errTab)) {
        $toMail = getUser()->getEmail();
        $toName = ucfirst(getUser()->getFirstname());

        $ramassage = false;

        // inscription auto-acceptée
        if ($auto_accept) {
            LegacyContainer::get('legacy_mailer')->send(getUser()->getEmail(), 'transactional/sortie-participation-confirmee', [
                'role' => $role_evt_join,
                'event_name' => $evtName,
                'event_url' => $evtUrl,
                'event_date' => $evtDate,
                'commission' => $commissionTitle,
            ]);
        } elseif (!$filiations) {
            // inscription simple de moi à moi
            LegacyContainer::get('legacy_mailer')->send(getUser()->getEmail(), 'transactional/sortie-demande-inscription-confirmation', [
                'role' => $role_evt_join,
                'event_name' => $evtName,
                'event_url' => $evtUrl,
                'event_date' => $evtDate,
                'commission' => $commissionTitle,
                'inscrits' => [
                    [
                        'firstname' => ucfirst(getUser()->getFirstname()),
                        'lastname' => strtoupper(getUser()->getLastname()),
                        'nickname' => getUser()->getNickname(),
                        'email' => getUser()->getEmail(),
                    ],
                ],
                'covoiturage' => $is_covoiturage,
            ]);
        } else {
            LegacyContainer::get('legacy_mailer')->send(getUser()->getEmail(), 'transactional/sortie-demande-inscription-confirmation', [
                'role' => $role_evt_join,
                'event_name' => $evtName,
                'event_url' => $evtUrl,
                'event_date' => $evtDate,
                'commission' => $commissionTitle,
                'inscrits' => array_map(function ($cetinscrit) {
                    return [
                        'firstname' => ucfirst($cetinscrit['firstname_user']),
                        'lastname' => strtoupper($cetinscrit['lastname_user']),
                        'nickname' => $cetinscrit['nickname_user'],
                        'email' => $cetinscrit['email_user'],
                    ];
                }, $inscrits),
                'covoiturage' => $is_covoiturage,
            ]);
        }
    }
}

if (!$errTab && $evtUrl) {
    header('Location: ' . $evtUrl);
    exit;
}
