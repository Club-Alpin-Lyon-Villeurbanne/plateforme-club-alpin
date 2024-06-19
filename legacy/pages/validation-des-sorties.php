<?php

use App\Legacy\LegacyContainer;

$notif_validerunesortie_president = 0;

$MAX_SORTIES_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_SORTIES_VALIDATION');
$MAX_TIMESTAMP_FOR_LEGAL_VALIDATION = strtotime(LegacyContainer::getParameter('legacy_env_MAX_TIMESTAMP_FOR_LEGAL_VALIDATION'));

if (allowed('evt_legal_accept')) {
    // Pour chaque sortie non validee dans le timing demandé, et publiée
    $req = 'SELECT COUNT(id_evt)
			FROM caf_evt, caf_commission
			WHERE status_legal_evt = 0
			AND status_evt = 1
			AND commission_evt = id_commission
			AND tsp_evt > ' . time() . '
			AND tsp_evt < ' . $MAX_TIMESTAMP_FOR_LEGAL_VALIDATION . '
			ORDER BY tsp_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie_president = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));

    // sorties à valider (pagination)
    // compte
    $limite = $MAX_SORTIES_VALIDATION;
    $compte = $notif_validerunesortie_president; // nombre total d'evts à valider, défini plus haut
    // page ?
    $pagenum = (int) $p2;
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1
    $nbrPages = ceil($compte / $limite);

    // requetes pour les sorties en attente de validation par le president
    $req = 'SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
				, join_start_evt, cycle_master_evt, cycle_parent_evt
				, nickname_user
				, title_commission, code_commission
	FROM caf_evt, caf_user, caf_commission
	WHERE status_evt=1
	AND status_legal_evt=0
	AND tsp_evt > ' . time() . '
	AND tsp_evt < ' . $MAX_TIMESTAMP_FOR_LEGAL_VALIDATION . '

	AND id_user = user_evt
	AND commission_evt=id_commission
	ORDER BY tsp_evt ASC
	LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";

    $evtStandby = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
        require __DIR__ . '/../includes/evt-temoin-reqs.php';

        // ajout au tableau
        $evtStandby[] = $handle;
    }
}

?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<h1>Validation des sorties en tant que sortie officielle du CAF</h1>
			<?php
            if (!allowed('evt_legal_accept')) {
                echo '<p class="erreur">Droits insuffisants pour afficher cette page.</p>';
            } else {
                inclure($p1 . '-main', 'vide');

                echo '<h2>' . $notif_validerunesortie_president . ' Sortie' . ($notif_validerunesortie_president > 1 ? 's' : '') . ' non validée' . ($notif_validerunesortie_president > 1 ? 's' : '') . ', en attente de validation :</h2>';

                // liste :
                if (!$notif_validerunesortie_president) {
                    echo '<p class="info">Aucune sortie n\'est en attente de validation pour l\'instant.</p>';
                } else {
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
                                            . '<a class="nice2" href="/sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant validation" target="_blank">Aperçu / validation de la page</a> '

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