<?php

use App\Legacy\LegacyContainer;

// cette même page sert à afficher une liste de sortie pour plusieurs cas de figure définis dans la variable GET $p3

if (user()) {
    ?>
	<div class="main-type">
		<h1>Profil : mes sorties</h1>

		<?php inclure('profil-sorties-'.$p3, 'vide'); ?>
		<br />

		<?php
        // les requetes sont effectuées en fonction de la var $p3
        $evtList = [];

    // éléments constants sur chaque requête :
    $select = 'id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, join_max_evt, join_start_evt
				, nickname_user
				, title_commission, code_commission ';
    $from = '	caf_evt
				, caf_user
				, caf_commission ';

    // pagniation
        $limite = 10; // n elts par page
        $total = 0; // n elts en tout dans la base
        $pagenum = (int) ($_GET['pagenum'] ?? 0);
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1

    // LES SORTIES QUE J'AI CREE
    if ('self' == $p3) {
        $req = "
				SELECT SQL_CALC_FOUND_ROWS $select
				FROM $from
				WHERE id_user = user_evt
				AND user_evt=".getUser()->getId().'
				AND id_commission = commission_evt
				ORDER BY tsp_evt IS NOT NULL, tsp_evt DESC
				LIMIT '.($limite * ($pagenum - 1)).", $limite";

        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // calcul tu total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $use = true;

            // compte places totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
            require __DIR__.'/../includes/evt-temoin-reqs.php';

            $evtList[] = $handle;
        }
    }

    // SORTIES AUXQUELLES JE SUIS INSCRIT - PASSEES
    if ('prev' == $p3) {
        $req = "
				SELECT SQL_CALC_FOUND_ROWS $select
					, role_evt_join
				FROM $from
					, caf_evt_join
				WHERE status_evt=1
				AND status_evt_join=1

				AND id_commission = commission_evt
				AND tsp_end_evt < ".time().' '
                // jointure avec la table participation
                .'AND evt_evt_join = id_evt
				AND user_evt_join = '.getUser()->getId().'
				AND user_evt_join = id_user '
                // de la plus récente a la plus ancienne
                .'ORDER BY tsp_evt IS NOT NULL, tsp_evt DESC
				LIMIT '.($limite * ($pagenum - 1)).", $limite";

        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // calcul tu total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $use = true;

            // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
            require __DIR__.'/../includes/evt-temoin-reqs.php';

            $evtList[] = $handle;

            // AJOUT POST BETA 2 : affichage des suites de cycle
            $req = "
					SELECT SQL_CALC_FOUND_ROWS $select
						, role_evt_join
					FROM $from
						, caf_evt_join
					WHERE

					cycle_parent_evt = ".$handle['id_evt'].'

					AND status_evt=1
					AND status_evt_join=1

					AND id_commission = commission_evt
					AND tsp_end_evt < '.time().' '
                    // jointure avec la table participation
                    .'AND evt_evt_join = id_evt
					AND user_evt_join = '.getUser()->getId().'
					AND user_evt_join = id_user '
                    // de la plus récente a la plus ancienne
                    .'ORDER BY  `tsp_evt` DESC
					LIMIT '.($limite * ($pagenum - 1)).", $limite";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            // calcul tu total grâce à SQL_CALC_FOUND_ROWS
            $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
            $total += getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));
            while ($handle = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
                require __DIR__.'/../includes/evt-temoin-reqs.php';
                $evtList[] = $handle;
            }
        }
    }

    // SORTIES AUXQUELLES JE SUIS INSCRIT - FUTURES
    if ('next' == $p3) {
        $req = "
				SELECT SQL_CALC_FOUND_ROWS $select
					, role_evt_join
				FROM $from
					, caf_evt_join
				WHERE
				    status_evt=1
				    AND status_evt_join=1
				    AND id_commission = commission_evt
				    AND tsp_end_evt >= ".time().' '
                // jointure avec la table participation
                    .' AND evt_evt_join = id_evt
				    AND user_evt_join = '.getUser()->getId().'
				    AND user_evt_join = id_user '
                // de la plus prochaine a la plus lointaine
                    .' ORDER BY  tsp_evt ASC
				    LIMIT '.($limite * ($pagenum - 1)).", $limite";

        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // calcul tu total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $use = true;

            // compte places totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
            require __DIR__.'/../includes/evt-temoin-reqs.php';
            $evtList[] = $handle;

            // AJOUT POST BETA 2 : affichage des suites de cycle
            $req = "
					SELECT SQL_CALC_FOUND_ROWS $select
						, role_evt_join
					FROM $from
						, caf_evt_join
					WHERE

					cycle_parent_evt = ".$handle['id_evt'].'

					AND status_evt=1
					AND status_evt_join=1
					AND id_commission = commission_evt
					AND tsp_end_evt >= '.time().' '
                    // jointure avec la table participation
                    .'AND evt_evt_join = id_evt
					AND user_evt_join = '.getUser()->getId().'
					AND user_evt_join = id_user '
                    // de la plus prochaine a la plus lointaine
                    .'ORDER BY  `tsp_evt` ASC
					LIMIT '.($limite * ($pagenum - 1)).", $limite";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            // calcul tu total grâce à SQL_CALC_FOUND_ROWS
            $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
            $total += getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));
            while ($handle = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
                require __DIR__.'/../includes/evt-temoin-reqs.php';
                $evtList[] = $handle;
            }
        }
    }

    // PAGES ?
    $nbrPages = ceil($total / $limite);

    // ************
    // ** AFFICHAGE, on recupere le design de l'agenda

    // Rien ?
    if (!count($evtList)) {
        echo '<p class="info">Vous n\'avez pas encore de sortie à afficher ici.</p>';
    } else {
        echo '<p class="mini">'.$total.' sortie'.($total > 1 ? 's' : '').'</p>';
    }

    // Si trouvé
    if (count($evtList)) { ?>

			<!-- affichons tout ça dans le meme tableau que l'agenda -->
			<table id="agenda">
			<?php
                for ($i = 0; $i < count($evtList); ++$i) {
                    $evt = $evtList[$i];

                    $empiete = empietement_sortie((string) getUser()->getId(), $evt);

                    $status_evt = (0 == $evt['status_evt'] && user() && $evt['user_evt'] == (string) getUser()->getId() ? '<p class="alerte">Sortie en attente de publication</p>' : '')
                        .(1 == $evt['status_evt'] && user() && $evt['user_evt'] == (string) getUser()->getId() ? '<p class="info">Sortie publiée sur le site</p>' : '')
                        .(2 == $evt['status_evt'] && user() && $evt['user_evt'] == (string) getUser()->getId() ? '<p class="erreur">Sortie refusée et non publiée</p>' : '');

                    if (0 == $evt['status_evt'] && user() && $evt['user_evt'] == (string) getUser()->getId() && null === $evt['tsp_evt']) {
                        $status_evt = '<p class="alerte">Sortie à finaliser</p>';
                    }

                    echo '<tr>'
                            .'<td class="agenda-gauche">'
                                .($evt['tsp_evt'] !== null ? jour(date('N', $evt['tsp_evt']), 'short').' '.date('d', $evt['tsp_evt']).' '.mois(date('m', $evt['tsp_evt'])) : '').
                                // STATUT si j'en suis l'auteur :
                                $status_evt
                            .'</td>'
                            .'<td>';
                    require __DIR__.'/../includes/evt-tools.php';
                    require __DIR__.'/../includes/agenda-evt-debut.php';

                    if (count($empiete)) {
                        echo '<div class="empietements">
                                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Attention au timing :</b> ';
                        foreach ($empiete as $tmpJoin) {
                            // préinscrit
                            if (0 == $tmpJoin['status_evt_join']) {
                                echo '<br />- Adhérent pré-inscrit sur <a href="/sortie/'.$tmpJoin['code_evt'].'-'.$tmpJoin['id_evt'].'.html" title="">'.html_utf8($tmpJoin['titre_evt']).'</a> ';
                            }
                            // inscrit confirmé
                            if (1 == $tmpJoin['status_evt_join']) {
                                echo '<br />- Adhérent <span style="color:red">confirmé</span> sur <a href="/sortie/'.$tmpJoin['code_evt'].'-'.$tmpJoin['id_evt'].'.html" title="">'.html_utf8($tmpJoin['titre_evt']).'</a>';
                            }
                        }
                        echo '</div>';
                    }

                    echo '</td>'
                        .'</tr>';
                }
            ?>
			</table>
			<?php
        }

    // NAV - PAGES
    if ($total > $limite) {
        echo '<hr /><nav class="pageSelect">';
        for ($i = 1; $i <= $nbrPages; ++$i) {
            echo '<a href="'.$p1.'/'.$p2.'/'.$p3.'.html?pagenum='.$i.'" title="" class="'.($pagenum == $i ? 'up' : '').'">p'.$i.'</a> '.($i < $nbrPages ? '  ' : '');
        }
        echo '</nav>';
    } ?>
	</div>
	<?php
}
