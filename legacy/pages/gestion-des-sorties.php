<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$MAX_SORTIES_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_SORTIES_VALIDATION');
$notif_validerunesortie = 0;

// NOTIFICATIONS EVTS
if (allowed('evt_validate_all')) { // pouvoir de valider toutes les sorties de ttes commission confondues
    // compte des sorties à valider
    $req = 'SELECT COUNT(id_evt)
	FROM caf_evt, caf_user
	WHERE status_evt=0
    AND tsp_evt IS NOT NULL
    AND is_draft=0
	AND id_user=user_evt '
    . 'ORDER BY tsp_crea_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
} elseif (allowed('evt_validate')) { // pouvoir de valider les sorties d'un nombre N de commissions dont nous sommes ersponsable
    // recuperation des commissions sous notre joug
    $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('evt_validate');

    // compte des sorties à valider, selon la (les) commission dont nous sommes responsables
    $req = "SELECT COUNT(id_evt) FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND is_draft=0
		AND id_user=user_evt
		AND commission_evt=id_commission
		AND (code_commission LIKE '" . implode("' OR code_commission LIKE '", $tab) . "') " // condition OR pour toutes les commissions autorisées
        . 'ORDER BY tsp_crea_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
}

// GESTION DES SORTIES
if (allowed('evt_validate_all') || allowed('evt_validate')) {
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
        $req = 'SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt
					, join_start_evt
					, nickname_user
					, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND is_draft=0
		AND id_user = user_evt
		AND commission_evt=id_commission '
        . 'ORDER BY tsp_evt ASC
		LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";
    }

    // requetes pour SEULEMENT les sorties DES COMMISSION que nous sommes autorisées à administrer
    elseif (allowed('evt_validate')) { // commission non précisée ici = autorisation passée
        // recuperation des commissions sous notre joug
        $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('evt_validate');

        // sorties à valider, selon la (les) commission dont nous sommes responsables
        $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt
					, join_start_evt, is_draft
					, nickname_user
					, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND is_draft=0
		AND id_user=user_evt
		AND commission_evt=id_commission
		AND (code_commission LIKE '" . implode("' OR code_commission LIKE '", $tab) . "') " // condition OR pour toutes les commissions autorisées
        . 'ORDER BY tsp_crea_evt ASC
		LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";
    }

    $evtStandby = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
        require __DIR__ . '/../includes/evt-temoin-reqs.php';

        // ajout au tableau
        $evtStandby[] = $handle;
    }
}
// LISTE DES USERS / ADHERENTS
elseif (('adherents' == $p1 && allowed('user_see_all')) || ('admin-users' == $p1 && isGranted(SecurityConstants::ROLE_ADMIN))) {
    $userTab = [];
    $show = 'valid';
    // fonctions disponibles
    if (isset($_GET['show']) && in_array($_GET['show'], ['all', 'manual', 'notvalid', 'nomade', 'dels', 'expired', 'valid-expired'], true)) {
        $show = $_GET['show'];
    }
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);

    $req = 'SELECT id_user , email_user , cafnum_user , firstname_user , lastname_user , nickname_user , created_user , birthday_user , tel_user , tel2_user , adresse_user, cp_user ,  ville_user ,  civ_user , valid_user , manuel_user, nomade_user, date_adhesion_user, doit_renouveler_user
		FROM  `caf_user` '
        . ('dels' == $show ? ' WHERE valid_user=2 ' : '')
        . ('manual' == $show ? ' WHERE manuel_user=1 ' : '')
        . ('nomade' == $show ? ' WHERE nomade_user=1 ' : '')
        . ('valid' == $show ? ' WHERE valid_user=1 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('notvalid' == $show ? ' WHERE valid_user=0 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('expired' == $show ? ' WHERE valid_user=0 AND doit_renouveler_user=1 ' : '')
        . ('valid-expired' == $show ? ' WHERE valid_user=1 AND doit_renouveler_user=1 ' : '')
        . ' ORDER BY lastname_user ASC, lastname_user ASC
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

?>


<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<h1>Publication des sorties</h1>
			<?php
            if (!allowed('evt_validate')) {
                echo '<p class="erreur">Droits insuffisants pour afficher cette page.</p>';
            } else {
                inclure($p1 . '-main', 'vide');

                // liste :
                if (!$notif_validerunesortie) {
                    echo '<p class="info">Aucune sortie n\'est en attente de publication pour l\'instant.</p>';
                } else {
                    echo '<h2>' . $notif_validerunesortie . ' Sortie' . ($notif_validerunesortie > 1 ? 's' : '') . ' proposée' . ($notif_validerunesortie > 1 ? 's' : '') . ', en attente de publication :</h2>';
                    // ************
                    // ** AFFICHAGE, on recupere le design de l'agenda?>
					<!-- affichons tout ça dans le meme tableau que l'agenda -->
					<table id="agenda">
						<?php
                        for ($i = 0; $i < count($evtStandby); ++$i) {
                            $evt = $evtStandby[$i];

                            echo '<tr>'
                                    . '<td class="agenda-gauche">' . jour(date('N', $evt['tsp_evt']), 'short') . ' ' . date('d', $evt['tsp_evt']) . ' ' . mois(date('m', $evt['tsp_evt'])) . '</td>'
                                    . '<td>'

                                        // Boutons
                                        . '<div class="evt-tools">'

                                            // apercu
                                            . '<a class="nice2" href="/sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication" target="_blank">Aperçu</a> ';

                            // Modération
                            echo '
											<form action="' . generateRoute('sortie_validate', ['id' => (int) $evt['id_evt']]) . '" method="post" style="display:inline" class="loading">
												<input type="hidden" name="csrf_token" value="' . csrfToken('sortie_validate') . '" />
												<input type="submit" value="Autoriser &amp; publier" class="nice2 green" title="Autorise instantanément la publication de la sortie" />
											</form>

											<input type="button" value="Refuser" class="nice2 red" onclick="modal.show($(this).next().html())" title="Ne pas autoriser la publication de cette sortie. Vous devrez ajouter un message au créateur de la sortie." />
											<div style="display:none" id="refuser-' . (int) $evt['id_evt'] . '">
                                                <form action="' . generateRoute('sortie_refus', ['id' => (int) $evt['id_evt']]) . '" method="post" class="loading">
                                                    <input type="hidden" name="csrf_token" value="' . csrfToken('sortie_refus') . '" />

													<p>Laissez un message à l\'auteur pour lui expliquer la raison du refus :</p>
													<input type="text" name="msg" class="type1" placeholder="ex: Mauvais point de RDV" />
													<input type="submit" value="Refuser la publication" class="nice2 red" />
													<input type="button" value="Annuler" class="nice2" onclick="$.fancybox.close()" />
												</form>
											</div>
											<a class="nice2 noprint red" href="/supprimer-une-sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html" title="Supprimer définitivement la sortie"><img src="/img/base/x2.png" alt="" title="" style="" />&nbsp;&nbsp;Supprimer cette sortie</a>
											'
                                        . '</div>';

                            require __DIR__ . '/../includes/agenda-evt-debut.php';
                            echo '</td>'
                                . '</tr>';
                        } ?>
					</table>

					<?php

                    // vars utiles pour affichage de chaque élément
                    $linkit = false;
                    $addClass = 'standby';
                    for ($i = 0; $i < count($evtStandby); ++$i) {
                        $evt = $evtStandby[$i];
                        if ($i % 2) {
                            $pair = true;
                        } else {
                            $pair = false;
                        }
                    }
                }

                // PAGES
                if ($nbrPages > 1) {
                    echo '<nav class="pageSelect"><hr />';
                    for ($i = 1; $i <= $nbrPages; ++$i) {
                        echo '<a href="' . $p1 . '/' . $i . '.html" title="" class="' . ($pagenum == $i ? 'up' : '') . '">P' . $i . '</a> ' . ($i < $nbrPages ? '  ' : '');
                    }
                    echo '</nav>';
                }
            }
?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>


	<br style="clear:both" />
</div>