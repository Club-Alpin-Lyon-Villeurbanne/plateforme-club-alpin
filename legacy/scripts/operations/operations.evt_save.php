<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

$htmlSanitizer = new HtmlSanitizer(
    (new HtmlSanitizerConfig())->allowSafeElements()
);

// continuons... Création de l'evt en lui meme
if (!isset($errTab) || 0 === count($errTab)) {
    $description_evt = $htmlSanitizer->sanitize($description_evt);

    // sécurisation BDD
    $titre_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($titre_evt);
    $tarif_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tarif_evt);
    $tarif_detail = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tarif_detail);
    $massif_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($massif_evt);
    $rdv_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($rdv_evt);
    $tsp_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tsp_evt);
    $tsp_end_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tsp_end_evt);
    $tsp_evt_day = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tsp_evt_day);
    $tsp_evt_hour = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tsp_evt_hour);
    $tsp_end_evt_day = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tsp_end_evt_day);
    $tsp_end_evt_hour = LegacyContainer::get('legacy_mysqli_handler')->escapeString($tsp_end_evt_hour);
    $matos_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($matos_evt);
    $itineraire = LegacyContainer::get('legacy_mysqli_handler')->escapeString($itineraire);
    $difficulte_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($difficulte_evt);
    $description_evt = LegacyContainer::get('legacy_mysqli_handler')->escapeString($description_evt);

    if (0 == $id_groupe) {
        $id_groupe = null;
    } else {
        $id_groupe = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_groupe);
    }
    if (!empty($tarif_evt) && !is_numeric($tarif_evt)) {
        $errTab[] = "Erreur dans le champ 'Tarif' : " . $tarif_evt . " n'est pas une valeur numérique";
    }
    if ('0.00' == $tarif_evt || empty($tarif_evt)) {
        $tarif_evt = 'NULL';
    }
    if ('0.00' == $distance_evt || empty($distance_evt)) {
        $distance_evt = 'NULL';
    }
    if ('0' == $denivele_evt || empty($denivele_evt)) {
        $denivele_evt = 'NULL';
    }

    // code : juste pour un formatage explicite des URL vers les sorties
    $code_evt = substr(formater($titre_evt, 3), 0, 30);

    if ('evt_create' == $_POST['operation']) {
        $req = "INSERT INTO caf_evt(status_evt ,status_legal_evt ,user_evt ,commission_evt ,tsp_evt ,tsp_end_evt ,tsp_crea_evt ,place_evt ,titre_evt ,code_evt ,massif_evt ,rdv_evt ,tarif_evt, tarif_detail, denivele_evt ,distance_evt ,lat_evt ,long_evt ,matos_evt ,itineraire, difficulte_evt ,description_evt , need_benevoles_evt , join_start_evt, join_max_evt, ngens_max_evt, cycle_master_evt ,cycle_parent_evt ,child_version_from_evt ,child_version_tosubmit, id_groupe, cancelled_evt)
					VALUES ('0', '0', '$user_evt', '$commission_evt', '$tsp_evt', '$tsp_end_evt', '$tsp_crea_evt', '" . ($place_evt ?? '') . "', '$titre_evt', '$code_evt', '$massif_evt', '$rdv_evt', $tarif_evt, '$tarif_detail', '$denivele_evt', '$distance_evt', '$lat_evt', '$long_evt', '$matos_evt', '$itineraire', '$difficulte_evt', '$description_evt', $need_benevoles_evt , '$join_start_evt', '$join_max_evt', '$ngens_max_evt', '$cycle_master_evt', " . ($cycle_parent_evt ? "'$cycle_parent_evt'" : 'null') . ", '0', '0', " . ($id_groupe ?: 'null') . ", '0');";
    } elseif (isset($_POST['operation']) && 'evt_update' == $_POST['operation']) {
        // MISE A JOUR de l'éléments existant // IMPORTANT : le status repasse à 0
        $req = "UPDATE caf_evt SET `status_evt`=0,
				`tsp_evt`='$tsp_evt',
				`tsp_end_evt` =  '$tsp_end_evt',
				`tsp_edit_evt` =  '" . time() . "',
				`titre_evt` =  '$titre_evt',
				`code_evt` =  '$code_evt',
				`massif_evt` =  '$massif_evt',
				`rdv_evt` =  '$rdv_evt',
				`tarif_evt` =  $tarif_evt,
				`tarif_detail` =  '$tarif_detail',
				`denivele_evt` =  '$denivele_evt',
				`distance_evt` =  '$distance_evt',
				`lat_evt` =  '$lat_evt',
				`long_evt` =  '$long_evt',
				`matos_evt` =  '$matos_evt',
				`itineraire` =  '$itineraire',
				`difficulte_evt` =  '$difficulte_evt',
				`join_start_evt` =  '$join_start_evt',
				`join_max_evt` =  '$join_max_evt',
				`ngens_max_evt` =  '$ngens_max_evt',
				`description_evt` =  '$description_evt',
				`need_benevoles_evt` =  '$need_benevoles_evt'";
        if (null != $id_groupe) {
            $req .= ", id_groupe = '$id_groupe' ";
        }
        if (0 == $cycle_master_evt) {
            $req .= ', cycle_master_evt = 0 ';
        } else {
            $req .= ", cycle_master_evt = '$cycle_master_evt' ";
        }
        if (!$cycle_parent_evt) {
            $req .= ', cycle_parent_evt = null ';
        } else {
            $req .= ", cycle_parent_evt = '$cycle_parent_evt' ";
            $req2 = "UPDATE caf_evt SET cycle_master_evt = 1 WHERE id_evt=$cycle_parent_evt";
            LegacyContainer::get('legacy_mysqli_handler')->query($req2);
        }

        $req .= " WHERE  `caf_evt`.`id_evt` =$id_evt";
    }

    // on enregistre la sortie
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL creation/update : ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
    } else {
        // jointures de l'ev avec les users spécifiés (encadrant, coenc' benev')

        if ('evt_create' == $_POST['operation']) {
            $id_evt = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }

        $deja_encadrants = [];

        if ('evt_update' == $_POST['operation']) {
            // suppression des inscrits si ils ont un role encadrant/coencadrant dans cette sortie
            // suppression des inscriptions précédentes encadrant/coencadrant/benevole

            $req = "SELECT * FROM caf_evt_join WHERE evt_evt_join = $id_evt AND role_evt_join IN ('encadrant', 'coencadrant', 'stagiaire')";
            $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            if ($results) {
                while ($row = $results->fetch_assoc()) {
                    $deja_encadrants[] = $row['user_evt_join'];
                }
            }

            $new_encadrants = array_merge($encadrants, $coencadrants, $stagiaires);
            foreach ($deja_encadrants as $id_encadrant) {
                if (in_array($id_encadrant, $new_encadrants, true)) {
                    // on ne touche pas, il reste avec son statut
                }
                // L'utilisateur n'a plus de statut co-encadrant, on le supprime
                else {
                    $req = "DELETE FROM caf_evt_join WHERE evt_evt_join = $id_evt AND user_evt_join = $id_encadrant;";
                    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                        $errTab[] = 'Erreur SQL au nettoyage des jointures';
                    }
                }
            }
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            foreach ($encadrants as $id_user) {
                if (!in_array($id_user, $deja_encadrants, true)) {
                    $req = "INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(1,               '$id_evt',  '$id_user',  'encadrant', " . time() . ');';
                    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                }
            }
            foreach ($stagiaires as $id_user) {
                if (!in_array($id_user, $deja_encadrants, true)) {
                    $req = "INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(1,               '$id_evt',  '$id_user',  'stagiaire', " . time() . ');';
                    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                }
            }
            foreach ($coencadrants as $id_user) {
                if (!in_array($id_user, $deja_encadrants, true)) {
                    $req = "INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(1, '$id_evt',  '$id_user',  'coencadrant', " . time() . ');';
                    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                }
            }
            // Seulement en création :
            if ('evt_create' == $_POST['operation']) {
                foreach ($benevoles as $id_user) {
                    $id_user = (int) $id_user;
                    $req = "INSERT INTO caf_evt_join(status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(1, '$id_evt',  '$id_user',  'benevole', " . time() . ');';
                    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                        $errTab[] = 'Erreur SQL: ' . LegacyContainer::get('legacy_mysqli_handler')->lastError();
                    }
                }
            }
        }
    }
}

// All good
if (!isset($errTab) || 0 === count($errTab)) {
    // S'il ne s'agit pas d'un cycle :
    if (!$cycle_master_evt && !$cycle_parent_evt) {
        // L'auteur de la sortie est redirigé vers son espace perso > ses sorties, avec un message "Attente de validation"
        header('Location: /profil/sorties/self.html?lbxMsg=evt_create_success');
    }
    // si cet evt est le premier d'un cycle, on reste sur la même page pour inciter à la création d'un nouvel événement dans ce cycle
    else {
        if ($_POST['operation'] = 'evt_update') {
            unset($id_evt);
            unset($id_evt_to_update);
        }

        if ($cycle_master_evt) {
            // var pour le blocage de certaines options sur la page
            $suiteDeCycle = true;
            // message à afficher
            $lbxMsg = 'evt_create_success_newcycle';
            // on redirige vers la même page, avec des variables forcées
            $_POST['cycle'] = 'child';
            $_POST['cycle_parent_evt'] = $id_evt;
            // RAZ
            $_POST['tsp_evt_day'] = '';
            $_POST['tsp_evt_hour'] = '';
            $_POST['tsp_end_evt_day'] = '';
            $_POST['tsp_end_evt_hour'] = '';
            // modification du titre
            if ('SUITE DE : ' != substr(strtoupper($_POST['titre_evt']), 0, 11)) {
                $_POST['titre_evt'] = 'SUITE DE : ' . $_POST['titre_evt'];
            }
        }
        // si cet evt est le N-ième d'un cycle, on reste sur la même page pour inciter à la création d'un nouvel événement dans ce cycle
        else {
            // var pour le blocage de certaines options sur la page
            $suiteDeCycle = true;
            // message à afficher
            $lbxMsg = 'evt_create_success_newcycle_2';
            // on redirige vers la même page, avec des variables forcées
            $_POST['cycle'] = 'child';
            // RAZ
            $_POST['tsp_evt_day'] = '';
            $_POST['tsp_evt_hour'] = '';
            $_POST['tsp_end_evt_day'] = '';
            $_POST['tsp_end_evt_hour'] = '';
        }

        header('Location: /creer-une-sortie/' . html_utf8($code_commission) . '.html?lbxMsg=' . $lbxMsg);
    }
}
