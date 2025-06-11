<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

$htmlSanitizer = new HtmlSanitizer(
    (new HtmlSanitizerConfig())->allowSafeElements()->allowRelativeMedias()->allowRelativeLinks()
);

// continuons... Création de l'evt en lui meme
if (!isset($errTab) || 0 === count($errTab)) {
    $description_evt = $htmlSanitizer->sanitize($description_evt);

    if (0 == $id_groupe) {
        $id_groupe = null;
    }
    if (!empty($tarif_evt) && !is_numeric($tarif_evt)) {
        $errTab[] = "Erreur dans le champ 'Tarif' : " . $tarif_evt . " n'est pas une valeur numérique";
    }
    if ('0.00' == $tarif_evt || empty($tarif_evt)) {
        $tarif_evt = 0;
    }
    if ('0.00' == $distance_evt || empty($distance_evt)) {
        $distance_evt = '';
    }
    if ('0' == $denivele_evt || empty($denivele_evt)) {
        $denivele_evt = '';
    }
    if (empty($place_evt)) {
        $place_evt = '';
    }

    // code : juste pour un formatage explicite des URL vers les sorties
    $code_evt = substr(formater($titre_evt, 3), 0, 30);

    if ('evt_create' == $_POST['operation']) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO caf_evt(status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, place_evt, titre_evt, code_evt, massif_evt, rdv_evt, tarif_evt, tarif_detail, denivele_evt, distance_evt, lat_evt, long_evt, matos_evt, itineraire, difficulte_evt, description_evt, need_benevoles_evt, join_start_evt, join_max_evt, ngens_max_evt, id_groupe, cancelled_evt, details_caches_evt) VALUES ('0', '0', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0', ?)");
        $stmt->bind_param('iisssssssssdssssssssssssss', $user_evt, $commission_evt, $tsp_evt, $tsp_end_evt, $tsp_crea_evt, $place_evt, $titre_evt, $code_evt, $massif_evt, $rdv_evt, $tarif_evt, $tarif_detail, $denivele_evt, $distance_evt, $lat_evt, $long_evt, $matos_evt, $itineraire, $difficulte_evt, $description_evt, $need_benevoles_evt, $join_start_evt, $join_max_evt, $ngens_max_evt, $id_groupe, $details_caches_evt);
    } elseif (isset($_POST['operation']) && 'evt_update' == $_POST['operation']) {
        // MISE A JOUR de l'éléments existant // IMPORTANT : le status repasse à 0
        $current_time = time();
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_evt SET `status_evt`=0, `tsp_evt`=?, `tsp_end_evt`=?, `tsp_edit_evt`=?, `titre_evt`=?, `code_evt`=?, `massif_evt`=?, `rdv_evt`=?, `tarif_evt`=?, `tarif_detail`=?, `denivele_evt`=?, `distance_evt`=?, `lat_evt`=?, `long_evt`=?, `matos_evt`=?, `itineraire`=?, `difficulte_evt`=?, `join_start_evt`=?, `join_max_evt`=?, `ngens_max_evt`=?, `description_evt`=?, `need_benevoles_evt`=?, `details_caches_evt`=?' . (null != $id_groupe ? ', id_groupe = ?' : '') . ' WHERE `caf_evt`.`id_evt` = ?');

        if (null != $id_groupe) {
            $stmt->bind_param('ssissssdssssssssssssssii', $tsp_evt, $tsp_end_evt, $current_time, $titre_evt, $code_evt, $massif_evt, $rdv_evt, $tarif_evt, $tarif_detail, $denivele_evt, $distance_evt, $lat_evt, $long_evt, $matos_evt, $itineraire, $difficulte_evt, $join_start_evt, $join_max_evt, $ngens_max_evt, $description_evt, $need_benevoles_evt, $details_caches_evt, $id_groupe, $id_evt);
        } else {
            $stmt->bind_param('ssissssdssssssssssssssi', $tsp_evt, $tsp_end_evt, $current_time, $titre_evt, $code_evt, $massif_evt, $rdv_evt, $tarif_evt, $tarif_detail, $denivele_evt, $distance_evt, $lat_evt, $long_evt, $matos_evt, $itineraire, $difficulte_evt, $join_start_evt, $join_max_evt, $ngens_max_evt, $description_evt, $need_benevoles_evt, $details_caches_evt, $id_evt);
        }
    }

    // on enregistre la sortie
    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL creation/update : ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
    } else {
        // jointures de l'ev avec les users spécifiés (encadrant, coenc' benev')

        if ('evt_create' == $_POST['operation']) {
            $id_evt = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
        $stmt->close();

        $deja_encadrants = [];

        if ('evt_update' == $_POST['operation']) {
            // suppression des inscrits si ils ont un role encadrant/coencadrant dans cette sortie
            // suppression des inscriptions précédentes encadrant/coencadrant/benevole

            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT * FROM caf_evt_join WHERE evt_evt_join = ? AND role_evt_join IN ('encadrant', 'coencadrant', 'stagiaire')");
            $stmt->bind_param('i', $id_evt);
            $stmt->execute();
            $results = $stmt->get_result();
            if ($results) {
                while ($row = $results->fetch_assoc()) {
                    $deja_encadrants[] = $row['user_evt_join'];
                }
            }
            $stmt->close();

            $new_encadrants = array_merge($encadrants, $coencadrants, $stagiaires);
            foreach ($deja_encadrants as $id_encadrant) {
                if (in_array($id_encadrant, $new_encadrants, true)) {
                    // on ne touche pas, il reste avec son statut
                }
                // L'utilisateur n'a plus de statut co-encadrant, on le supprime
                else {
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('DELETE FROM caf_evt_join WHERE evt_evt_join = ? AND user_evt_join = ?');
                    $stmt->bind_param('ii', $id_evt, $id_encadrant);
                    if (!$stmt->execute()) {
                        $errTab[] = 'Erreur SQL au nettoyage des jointures';
                    }
                    $stmt->close();
                }
            }
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            $current_time = time();
            foreach ($encadrants as $id_user) {
                if (!in_array($id_user, $deja_encadrants, true)) {
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("REPLACE INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join) VALUES(1, ?, ?, 'encadrant', ?)");
                    $stmt->bind_param('iii', $id_evt, $id_user, $current_time);
                    if (!$stmt->execute()) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                    $stmt->close();
                }
            }
            foreach ($stagiaires as $id_user) {
                if (!in_array($id_user, $deja_encadrants, true)) {
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("REPLACE INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join) VALUES(1, ?, ?, 'stagiaire', ?)");
                    $stmt->bind_param('iii', $id_evt, $id_user, $current_time);
                    if (!$stmt->execute()) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                    $stmt->close();
                }
            }
            foreach ($coencadrants as $id_user) {
                if (!in_array($id_user, $deja_encadrants, true)) {
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("REPLACE INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join) VALUES(1, ?, ?, 'coencadrant', ?)");
                    $stmt->bind_param('iii', $id_evt, $id_user, $current_time);
                    if (!$stmt->execute()) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                    $stmt->close();
                }
            }
            // Seulement en création :
            if ('evt_create' == $_POST['operation']) {
                foreach ($benevoles as $id_user) {
                    $id_user = (int) $id_user;
                    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join) VALUES(1, ?, ?, 'benevole', ?)");
                    $stmt->bind_param('iii', $id_evt, $id_user, $current_time);
                    if (!$stmt->execute()) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// All good
if (!isset($errTab) || 0 === count($errTab)) {
    // L'auteur de la sortie est redirigé vers son espace perso > ses sorties, avec un message "Attente de validation"
    header('Location: /profil/sorties/self?lbxMsg=evt_create_success');
}
