<?php

use App\Legacy\LegacyContainer;

$evt = false;
$id_evt = null;
$errPage = false; // message d'erreur spécifique à la page courante si besoin

if ($id_evt) {
    // selection complete, non conditionnelle par rapport au statut
    $req = "SELECT
            id_evt, code_evt, status_evt, status_legal_evt, status_who_evt, status_legal_who_evt,
                user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt,
                rdv_evt,titre_evt, massif_evt, tarif_evt,
                cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt,
                matos_evt, need_benevoles_evt, lat_evt, long_evt, join_start_evt, ngens_max_evt, join_max_evt,
                id_groupe, tarif_detail, distance_evt, itineraire,
            nickname_user, civ_user, firstname_user, lastname_user, tel_user,
            title_commission, code_commission, details_caches_evt
        FROM caf_evt as evt, caf_user as user, caf_commission as commission
        WHERE id_evt=$id_evt
            AND id_user = user_evt
            AND commission_evt=commission.id_commission
            LIMIT 1";

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $on_peut_voir = true;

        // on a le droit de voir cette page ?
        if (
            ($on_peut_voir && (1 == $handle['status_evt'])) // publiée
            || (allowed('evt_validate') && isset($_GET['forceshow']) && $_GET['forceshow']) // ou mode validateur
            || (allowed('evt_validate_all') && isset($_GET['forceshow']) && $_GET['forceshow']) // ou mode validateur
            || (user() && $handle['user_evt'] == (string) getUser()->getId()) // ou j'en suis l'auteur ? QUID de l'encadrant ?
        ) {
            $current_commission = $handle['code_commission'];

            // Groupe de niveau
            $handle['groupe'] = [];
            if (null != $handle['id_groupe']) {
                $req = 'SELECT * FROM `caf_groupe` WHERE `id` = ' . $handle['id_groupe'];
                $handleGroupe = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($groupe = $handleGroupe->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['groupe'] = $groupe;
                }
            }

            // participants integres a la sortie
            $handle['joins'] = ['inscrit' => [], 'manuel' => [], 'encadrant' => [], 'stagiaire' => [], 'coencadrant' => [], 'benevole' => [], 'enattente' => []];

            // participants "speciaux" avec droits :
            $req = "SELECT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                        , role_evt_join, is_covoiturage
                FROM caf_evt_join, caf_user
                WHERE evt_evt_join = $id_evt
                AND user_evt_join = id_user
                AND status_evt_join = 1
                AND
                    (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'stagiaire' OR role_evt_join LIKE 'coencadrant' OR role_evt_join LIKE 'benevole')
                ORDER BY firstname_user ASC, lastname_user ASC, id_user ASC
                LIMIT 300";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['joins'][$handle2['role_evt_join']][] = $handle2;
            }

            // participants "enattente" :
            $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                        , role_evt_join , is_covoiturage
                FROM caf_evt_join, caf_user
                WHERE evt_evt_join = ' . (int) $id_evt . '
                AND user_evt_join = id_user
                AND status_evt_join = 0
                ORDER BY firstname_user ASC, lastname_user ASC, id_user ASC
                LIMIT 300';

            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['joins']['enattente'][] = $handle2;
            }

            // participants "normaux" : inscrit en ligne : leur role est à "inscrit"
            $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                        , role_evt_join, is_covoiturage
                FROM caf_evt_join, caf_user
                WHERE evt_evt_join = ' . (int) $id_evt . "
                AND user_evt_join = id_user
                AND role_evt_join LIKE 'inscrit'
                AND status_evt_join = 1
                ORDER BY firstname_user ASC, lastname_user ASC, id_user ASC
                LIMIT 300";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['joins']['inscrit'][] = $handle2;
            }

            // participants "manuel" : inscrit par l'orga : leur role est à "manuel"
            $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                        , role_evt_join, is_covoiturage
                FROM caf_evt_join, caf_user
                WHERE evt_evt_join = ' . (int) $id_evt . "
                AND user_evt_join = id_user
                AND role_evt_join LIKE 'manuel'
                AND status_evt_join = 1
                ORDER BY firstname_user ASC, lastname_user ASC, id_user ASC
                LIMIT 300";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['joins']['manuel'][] = $handle2;
            }

            // mon rapport à cette sortie
            $monStatut = 'neutre';

            if (user()) {
                $req = "SELECT * FROM caf_evt_join
                    WHERE evt_evt_join=$id_evt
                    AND user_evt_join=" . getUser()->getId() . '
                    ORDER BY tsp_evt_join DESC
                    LIMIT 1';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    // si je suis pas encore validé
                    if (0 == $handle2['status_evt_join']) {
                        $monStatut = 'en attente';
                    }
                    // si je suis inscrit, "monStatut" prend la valeur de mon role
                    if (1 == $handle2['status_evt_join']) {
                        $monStatut = $handle2['role_evt_join'];
                    }
                    // si je suis refusé
                    if (2 == $handle2['status_evt_join']) {
                        $monStatut = 'refusé';
                    }
                }
            }

            $evt = $handle;
        } else {
            $errPage = 'Accès non autorisé';
        }
    }
}
