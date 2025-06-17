<?php

use App\Legacy\LegacyContainer;

if (!user()) {
    header('Location: /login');
    exit;
}

if ($p2) {
    // CREER UNE SORTIE : même page utilisée pour modifier une sortie, gérée ici si on passe un paramètre en "p3"
    $id_evt_to_update = false; // variable pour annoncer au formulaire qu'il s'agit d'un update et non d'une créa. Par defaut, créa : false
    $update_status = false;

    // LSITE DES ENCADRANTS AUTORISÉS À ASSOCIER À LA COMMISSION COURANTE
    // encadrants
    $encadrantsTab = [];
    $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
    $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user, description_user_attr
        FROM caf_user, caf_user_attr, caf_usertype
        WHERE doit_renouveler_user=0
        AND id_user =user_user_attr
        AND usertype_user_attr=id_usertype
        AND code_usertype='encadrant'
        AND params_user_attr='commission:$com'
        ORDER BY firstname_user ASC, lastname_user ASC";
    // CRI - 29/08/2015
    // Correctif car la commission du jeudi compte plus de 50 encadrants
    // LIMIT 0 , 50";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $encadrantsTab[] = $handle;
    }

    $stagiairesTab = [];
    $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
    $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
        FROM caf_user, caf_user_attr, caf_usertype
        WHERE doit_renouveler_user=0
        AND id_user =user_user_attr
        AND usertype_user_attr=id_usertype
        AND code_usertype='stagiaire'
        AND params_user_attr='commission:$com'
        ORDER BY firstname_user ASC, lastname_user ASC";
    // CRI - 29/08/2015
    // Correctif car la commission du jeudi compte plus de 50 encadrants
    // LIMIT 0 , 50";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $stagiairesTab[] = $handle;
    }

    // coencadrants
    $coencadrantsTab = [];
    $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
    $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user, description_user_attr
        FROM caf_user, caf_user_attr, caf_usertype
        WHERE doit_renouveler_user=0
        AND id_user =user_user_attr
        AND usertype_user_attr=id_usertype
        AND code_usertype='coencadrant'
        AND params_user_attr='commission:$com'
        ORDER BY firstname_user ASC, lastname_user ASC";
    // CRI - 29/08/2015
    // Correctif car la commission du jeudi compte plus de 50 encadrants
    // LIMIT 0 , 50";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $coencadrantsTab[] = $handle;
    }

    // benevoles
    $benevolesTab = [];
    $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
    $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
        FROM caf_user, caf_user_attr, caf_usertype
        WHERE doit_renouveler_user=0
        AND id_user =user_user_attr
        AND usertype_user_attr=id_usertype
        AND code_usertype='benevole'
        AND params_user_attr='commission:$com'
        ORDER BY firstname_user ASC, lastname_user ASC
        LIMIT 0 , 50";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $benevolesTab[] = $handle;
    }

    // MISE A JOUR
    if ($p3 && 'update-' == substr($p3, 0, 7)) {
        // un ID de sortie est vise, il s'agit d'une modif et non d'une creation
        $id_evt = (int) substr(strrchr($p3, '-'), 1);

        $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt
                , denivele_evt, distance_evt, matos_evt, difficulte_evt, description_evt, lat_evt, long_evt
                , ngens_max_evt
                , join_start_evt, join_max_evt, id_groupe, tarif_detail, need_benevoles_evt, itineraire
                , nickname_user
                , title_commission, code_commission, details_caches_evt
        FROM caf_evt, caf_user, caf_commission as commission
        WHERE id_evt=$id_evt
        AND id_user = user_evt
        AND commission_evt=commission.id_commission
        LIMIT 1";

        $handleTab = [];
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // variable pour annoncer au formulaire qu'il s'agit d'un update et non d'une creation
            $id_evt_to_update = $id_evt;
            $update_status = $handle['status_evt'];

            // Recup' encadrants,coencadrants,benevoles
            $encadrants = [];
            $stagiaires = [];
            $coencadrants = [];
            $benevoles = [];
            $req = "SELECT * FROM caf_evt_join WHERE evt_evt_join=$id_evt LIMIT 300";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                if ('encadrant' == $handle2['role_evt_join']) {
                    $encadrants[] = $handle2['user_evt_join'];
                }
                if ('stagiaire' == $handle2['role_evt_join']) {
                    $stagiaires[] = $handle2['user_evt_join'];
                }
                if ('coencadrant' == $handle2['role_evt_join']) {
                    $coencadrants[] = $handle2['user_evt_join'];
                }
                if ('benevole' == $handle2['role_evt_join']) {
                    $benevoles[] = $handle2['user_evt_join'];
                }
            }

            // benevoles
            $benevolesTab = [];
            if (count($benevoles) > 0) {
                $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
        FROM caf_user
        WHERE id_user IN (' . implode(',', $benevoles) . ')
        ORDER BY  lastname_user ASC
        LIMIT 0 , 50';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $benevolesTab[] = $handle2;
                }
            }

            // méthode "sale & rapide" : on remplace les valeurs POST par défaut, par celles issues de la BDD
            $_POST['commission_evt'] = $handle['commission_evt'];
            $_POST['titre_evt'] = $handle['titre_evt'];
            $_POST['encadrants'] = $encadrants;
            $_POST['stagiaires'] = $stagiaires;
            $_POST['coencadrants'] = $coencadrants;
            $_POST['benevoles'] = $benevoles;
            $_POST['tarif_evt'] = $handle['tarif_evt'];
            $_POST['tarif_detail'] = $handle['tarif_detail'];
            $_POST['details_caches_evt'] = $handle['details_caches_evt'];
            $_POST['massif_evt'] = $handle['massif_evt'];
            $_POST['id_groupe'] = $handle['id_groupe'];
            $_POST['itineraire'] = $handle['itineraire'];
            $_POST['rdv_evt'] = $handle['rdv_evt'];
            $_POST['lat_evt'] = $handle['lat_evt'];
            $_POST['long_evt'] = $handle['long_evt'];
            $_POST['tsp_evt_day'] = $handle['tsp_evt'] ? date('d/m/Y', $handle['tsp_evt']) : '';
            $_POST['tsp_evt_hour'] = $handle['tsp_evt'] ? date('H:i', $handle['tsp_evt']) : '';
            $_POST['tsp_end_evt_day'] = $handle['tsp_end_evt'] ? date('d/m/Y', $handle['tsp_end_evt']) : '';
            $_POST['tsp_end_evt_hour'] = $handle['tsp_end_evt'] ? date('H:i', $handle['tsp_end_evt']) : '';
            $_POST['denivele_evt'] = $handle['denivele_evt'];
            $_POST['ngens_max_evt'] = $handle['ngens_max_evt'];
            $_POST['distance_evt'] = $handle['distance_evt'];
            $_POST['matos_evt'] = $handle['matos_evt'];
            $_POST['difficulte_evt'] = $handle['difficulte_evt'];
            $_POST['description_evt'] = $handle['description_evt'];
            $_POST['join_max_evt'] = $handle['join_max_evt'];
            $_POST['need_benevoles_evt'] = $handle['need_benevoles_evt'];
            // special : tsp to days. le timestamp enregistré commence à minuit pile
            $_POST['join_start_evt_days'] = floor(($handle['tsp_evt'] - $handle['join_start_evt']) / 86400);
        }
    }
}

?>


<script type="text/javascript" src="/js/faux-select.js"></script>

<!-- MAIN -->
<div id="main" role="main" class="bigoo">

	<!-- partie gauche -->
	<div id="left1">
        <!-- // Titre. créa ou modif ? -->
        <?php
        if (isset($id_evt_to_update) && !$id_evt_to_update) {
            echo '<h1 class="page-h1">Proposer une <b>sortie</b></h1>';
        } else {
            echo '<h1 class="page-h1"><b>Modifier</b> cette sortie</h1>';
        }
?>

        <div style="padding:10px 0 0 30px; line-height:18px; ">
            <?php
    // je n'ai pas le droit de créer une sortie (peu importe quelle commission)
    if (!allowed('evt_create')) {
        echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de création de sortie.</p>';
    }

    // j'ai le droit, mais aucune commission n'est donnée
    elseif (!$p2) {
        echo '<p>Merci de sélectionner la commission visée pour cette sortie :</p>';
        // pour chaque comm que je peux modifier, lien
        foreach ($comTab as $tmp) {
            if (allowed('evt_create', 'commission:' . $tmp['code_commission'])) {
                echo '<a class="lien-big" style="color:black;" href="' . LegacyContainer::get('router')->generate('creer_sortie', ['code' => html_utf8($tmp['code_commission'])]) . '" title="">&gt; Créer une sortie <b>' . html_utf8($tmp['title_commission']) . '</b></a><br />';
            }
        }
    }

    // je n'ai pas le droit de créer une sortie pour cette commission
    elseif (!allowed('evt_create', 'commission:' . $p2)) {
        echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de création de sortie pour la commission ' . html_utf8($p2) . '.</p>';
    } elseif (getUser()->getDoitRenouveler()) {
        inclure('info-encadrant-licence-obsolete', 'vide');
    }

    // on a donné une commission pour laquelle j'ai les droits, alors go
    else {
        // modification de sortie actuellement publiée = message d'avertissement
        if ($id_evt_to_update && 1 == $update_status) {
            echo '<p class="alerte">Attention : si vous modifiez cette sortie, elle devra à nouveau être validée par un responsable avant d\'être affichée sur le site !</p>';
        }
        require __DIR__ . '/../includes/evt/creer.php';
    }
?>
        </div><br>

	</div><!-- fin left -->

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>

	<br style="clear:both" />
</div>