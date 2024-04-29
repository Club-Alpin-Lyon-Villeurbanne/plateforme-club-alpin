<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$MAX_SORTIES_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_SORTIES_VALIDATION');

// vars de notification
$notif_validerunesortie = 0;

// commission courante sur cette page
$current_commission = false;

// LISTE DES COMMISSIONS PUBLIQUES
$req = 'SELECT * FROM caf_commission WHERE vis_commission=1 ORDER BY ordre_commission ASC';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$comTab = [];
$comCodeTab = [];
while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // v2 :
    $comTab[$handle['code_commission']] = $handle;

    // définition de la variable de page 'current_commission' si elle est précisée dans l'URL
    if ($p2 == $handle['code_commission']) {
        $current_commission = $p2;
    }
    // variable de commission si elle est passée "en force" dans les vars GET
    elseif (($_GET['commission'] ?? null) == $handle['code_commission']) {
        $current_commission = $_GET['commission'];
    }
}

// NOTIFICATIONS EVTS
if (allowed('evt_validate_all')) { // pouvoir de valider toutes les sorties de ttes commission confondues
    // compte des sorties à valider
    $req = 'SELECT COUNT(id_evt)
	FROM caf_evt, caf_user
	WHERE status_evt=0
    AND tsp_evt IS NOT NULL
	AND id_user=user_evt '
    .'ORDER BY tsp_crea_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
} elseif (allowed('evt_validate')) { // pouvoir de valider les sorties d'un nombre N de commissions dont nous sommes ersponsable
    // recuperation des commissions sous notre joug
    $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('evt_validate');

    // compte des sorties à valider, selon la (les) commission dont nous sommes responsables
    $req = "SELECT COUNT(id_evt) FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND id_user=user_evt
		AND commission_evt=id_commission
		AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."') " // condition OR pour toutes les commissions autorisées
        .'ORDER BY tsp_crea_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
}

// PAGE SORTIE
elseif ('sortie' == $p1 || 'feuille-de-sortie' == $p1) {
    $evt = false;
    $id_evt = null;
    $errPage = false; // message d'erreur spécifique à la page courante si besoin

    if ('feuille-de-sortie' == $p1) {
        $type = strstr($p2, '-', true);
        switch ($type) {
            case 'evt':
                $id_evt = (int) substr(strrchr($p2, '-'), 1);
                break;
            default:
                break;
        }
    } elseif ('sortie' == $p1) {
        $id_evt = (int) substr(strrchr($p2, '-'), 1);
    }

    if ($id_evt) {
        // selection complete, non conditionnelle par rapport au statut
        $req = "SELECT
                id_evt, code_evt, status_evt, status_legal_evt, status_who_evt, status_legal_who_evt,
                    user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt,
                    rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt,
                    cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt,
                    matos_evt, need_benevoles_evt, lat_evt, long_evt, join_start_evt, ngens_max_evt, join_max_evt,
                    id_groupe, tarif_detail, distance_evt, itineraire,
                nickname_user, civ_user, firstname_user, lastname_user, tel_user,
                title_commission, code_commission
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
                || (allowed('evt_validate_all') && isset($_GET['forceshow']) &&  $_GET['forceshow']) // ou mode validateur
                || (user() && $handle['user_evt'] == (string) getUser()->getId()) // ou j'en suis l'auteur ? QUID de l'encadrant ?
            ) {
                $current_commission = $handle['code_commission'];

                // Groupe de niveau
                $handle['groupe'] = [];
                if (null != $handle['id_groupe']) {
                    $req = 'SELECT * FROM `caf_groupe` WHERE `id` = '.$handle['id_groupe'];
                    $handleGroupe = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($groupe = $handleGroupe->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['groupe'] = $groupe;
                    }
                }

                // participants integres a la sortie
                $handle['joins'] = ['inscrit' => [], 'manuel' => [], 'encadrant' => [], 'stagiaire' => [], 'coencadrant' => [], 'benevole' => [], 'enattente' => []];

                if ($handle['cycle_parent_evt']) {
                    // cette sortie fait partie d'un cycle, alors on ajoute un lien vers son parent
                    $req = '
                        SELECT id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, join_max_evt, join_start_evt
                            , nickname_user, civ_user
                            , title_commission, code_commission
                        FROM caf_evt
                            , caf_user
                            , caf_commission
                        WHERE id_user = user_evt
                        AND id_evt='.(int) $handle['cycle_parent_evt'].'
                        AND id_commission = commission_evt
                        ORDER BY  `tsp_crea_evt` DESC
                        LIMIT 1';

                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['cycleparent'] = $handle2;
                    }
                } elseif ($handle['cycle_master_evt']) {
                    // cette sortie est la premiere d'un cycle, on recupere les infos des sorties suivantes
                    $req = '
                        SELECT id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, title_commission, code_commission, tsp_evt, titre_evt, cycle_parent_evt
                        FROM caf_evt
                            , caf_commission
                        WHERE cycle_parent_evt='.(int) $id_evt.'
                        AND id_commission = commission_evt
                        ORDER BY `tsp_crea_evt` ASC
                        LIMIT 30';
                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['cyclechildren'][] = $handle2;
                    }
                }

                // participants "speciaux" avec droits :
                $req = "SELECT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join = $id_evt
                    AND user_evt_join = id_user
                    AND status_evt_join = 1
                    AND
                        (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'stagiaire' OR role_evt_join LIKE 'coencadrant' OR role_evt_join LIKE 'benevole')
                    LIMIT 300";
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins'][$handle2['role_evt_join']][] = $handle2;
                }

                // participants "enattente" :
                $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join , is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join  = '.(int) ($handle['cycle_parent_evt'] ?: $id_evt).'
                    AND user_evt_join = id_user
                    AND status_evt_join = 0
                    LIMIT 300';

                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins']['enattente'][] = $handle2;
                }

                // participants "normaux" : inscrit en ligne : leur role est à "inscrit"
                $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join  = '.(int) ($handle['cycle_parent_evt'] ?: $id_evt)."
                    AND user_evt_join = id_user
                    AND role_evt_join LIKE 'inscrit'
                    AND status_evt_join = 1
                    LIMIT 300";
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins']['inscrit'][] = $handle2;
                }

                // participants "manuel" : inscrit par l'orga : leur role est à "manuel"
                $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join  = '.(int) ($handle['cycle_parent_evt'] ?: $id_evt)."
                    AND user_evt_join = id_user
                    AND role_evt_join LIKE 'manuel'
                    AND status_evt_join = 1
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
                        AND user_evt_join=".getUser()->getId().'
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

                if ('sortie' == $p1) {
                    // AUTRES INFOS, PAS NECESSAIRE POUR LA FICHE DE SORTIE

                    // si la sortie est annulée, on recupère les details de "WHO" : qui l'a annulée
                    if ('1' == $handle['cancelled_evt']) {
                        $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, nomade_user, civ_user
                            FROM caf_user
                            WHERE id_user='.(int) $handle['cancelled_who_evt'].'
                            LIMIT 300';
                        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                            $handle['cancelled_who_evt'] = $handle2;
                        }
                    }

                    // si un compte rendu existe ?
                    $handle['cr'] = false;
                    $req = "SELECT id_article, titre_article, code_article
                        FROM caf_article
                        WHERE evt_article = $id_evt
                        AND status_article = 1
                        ORDER BY tsp_validate_article DESC
                        LIMIT 1";
                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['cr'] = $handle2;
                    }

                    // Modification des METAS de la page
                    $meta_title = $handle['titre_evt'].' | '.$p_sitename;
                    $meta_description = limiterTexte(strip_tags($handle['description_evt']), 200).'...';

                    // si je suis chef de famille (filiations) je rajoute la liste de mes "enfants" pour les inscrire
                    $filiations = [];
                    if (user() && getUser()->getCafnum()) {
                        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, birthday_user, civ_user, email_user, tel_user, cafnum_user FROM caf_user WHERE cafnum_parent_user LIKE '".LegacyContainer::get('legacy_mysqli_handler')->escapeString(getUser()->getCafnum())."' LIMIT 15";
                        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                            $filiations[] = $handle2;
                        }
                    }
                }

                // go
                $evt = $handle;
            } else {
                $errPage = 'Accès non autorisé';
            }
        }
    }
}
// GESTION DES SORTIES
elseif ('gestion-des-sorties' == $p1 && (allowed('evt_validate_all') || allowed('evt_validate'))) {
    // sorties à valider (pagination)
    // compte
    $limite = $MAX_SORTIES_VALIDATION;
    $compte = $notif_validerunesortie; // nombre total d'evts à valider, défini plus haut
    // page ?
    $pagenum = (int) $p2;
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1
    $nbrPages = ceil($compte / $limite);

    // requetes pour les sorties en attente de validation de cet user POUR TOUTES LES COMMISSIONS
    if (allowed('evt_validate_all')) {
        $req = 'SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
					, join_start_evt, cycle_master_evt, cycle_parent_evt
					, nickname_user
					, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND id_user = user_evt
		AND commission_evt=id_commission '
        .'ORDER BY tsp_evt ASC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    }

    // requetes pour SEULEMENT les sorties DES COMMISSION que nous sommes autorisées à administrer
    elseif (allowed('evt_validate')) { // commission non précisée ici = autorisation passée
        // recuperation des commissions sous notre joug
        $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('evt_validate');

        // sorties à valider, selon la (les) commission dont nous sommes responsables
        $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
					, join_start_evt, cycle_master_evt, cycle_parent_evt
					, nickname_user
					, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND id_user=user_evt
		AND commission_evt=id_commission
		AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."') " // condition OR pour toutes les commissions autorisées
        .'ORDER BY tsp_crea_evt ASC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    }

    $evtStandby = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
        require __DIR__.'/../includes/evt-temoin-reqs.php';

        // ajout au tableau
        $evtStandby[] = $handle;
    }
}
// LISTE DES USERS / ADHERENTS
elseif (('adherents' == $p1 && allowed('user_see_all')) || ('admin-users' == $p1 && admin())) {
    $userTab = [];
    $show = 'valid';
    // fonctions disponibles
    if (isset($_GET['show']) && in_array($_GET['show'], ['all', 'manual', 'notvalid', 'nomade', 'dels', 'expired', 'valid-expired'], true)) {
        $show = $_GET['show'];
    }
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);

    $req = 'SELECT id_user , email_user , cafnum_user , firstname_user , lastname_user , nickname_user , created_user , birthday_user , tel_user , tel2_user , adresse_user, cp_user ,  ville_user ,  civ_user , valid_user , manuel_user, nomade_user, date_adhesion_user, doit_renouveler_user
		FROM  `caf_user` '
        .('dels' == $show ? ' WHERE valid_user=2 ' : '')
        .('manual' == $show ? ' WHERE manuel_user=1 ' : '')
        .('nomade' == $show ? ' WHERE nomade_user=1 ' : '')
        .('valid' == $show ? ' WHERE valid_user=1 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        .('notvalid' == $show ? ' WHERE valid_user=0 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        .('expired' == $show ? ' WHERE valid_user=0 AND doit_renouveler_user=1 ' : '')
        .('valid-expired' == $show ? ' WHERE valid_user=1 AND doit_renouveler_user=1 ' : '')
        .' ORDER BY lastname_user ASC, lastname_user ASC
		LIMIT 9000';			// , pays_user

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $handleSql->fetch_assoc()) {
        if ('0' == $row['birthday_user'] || '1' == $row['birthday_user'] || '' == $row['birthday_user']) {
            // dans ces cas, bug très probable
            $row['birthday_user'] = 0;
        } else { // la date de naissance est remplacée par l'age (avec zéros inutiles, pour tri de la colonne)
            $row['birthday_user'] = sprintf('%03d', getYearsSinceDate($row['birthday_user']));
        }

        $userTab[] = $row;
    }
}