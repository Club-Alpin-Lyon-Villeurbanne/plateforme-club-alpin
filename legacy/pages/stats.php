<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

?>
<!-- MAIN -->
<div id="main" role="main" style="width:100%">
	<div style="padding:20px 10px;">

		<h1>Statistiques</h1>
		<p>
			<img src="/img/base/magnifier.png" style="vertical-align:middle" />
			Le champ "<i>Search</i>" en haut à droite du tableau vous permet de rechercher n'importe quelle valeur instantanément.<br />
			<img src="/img/base/database_go.png" style="vertical-align:middle" />
			Les boutons de droite vous permettent d'exporter le tableau courant, le plus utile étant l'exportation en .csv.<br />
			<img src="/img/base/info.png" style="vertical-align:middle" />
			Vous pouvez trier les résultats selon différents critères en même temps, en pressant la touche <i>Maj / Shift</i> en cliquant sur les titres des colonnes.<br />
		</p>


		<?php

        echo '<p>';
if (allowed('stats_commissions_read')) {
    echo '<a href="/stats/commissions.html" ' . ('commissions' == $p2 ? 'style="background:#d3d6ff"' : '') . ' class="boutonFancy">Statistiques par sorties</a> ';
}
if (allowed('stats_users_read')) {
    echo '<a href="/stats/users.html" ' . ('users' == $p2 ? 'style="background:#d3d6ff"' : '') . ' class="boutonFancy">Statistiques par adhérents</a> ';
}
if (allowed('article_create')) {
    echo '<a href="/stats/nbvues.html" ' . ('nbvues' == $p2 ? 'style="background:#d3d6ff"' : '') . ' class="boutonFancy">Statistiques articles</a> ';
}
echo '</p>';

if ((allowed('stats_commissions_read') || allowed('stats_users_read')) && ('commissions' == $p2 || 'users' == $p2)) {
    ?>
			<link rel="stylesheet" href="/tools/datatables/extras/TableTools/media/css/TableTools.css" type="text/css" media="screen" />
			<script type="text/javascript" src="/tools/datatables/extras/TableTools/media/js/TableTools.min.js"></script>

			<br />

			<!-- Params -->
			<form action="<?php echo $versCettePage; ?>">
				<b>Période :</b><br />
				du
				<input type="text" name="dateMin" class="type2" style="" value="<?php echo $dateMin ?? ''; ?>" placeholder="jj/mm/aaaa" />
				au
				<input type="text" name="dateMax" class="type2" style="" value="<?php echo $dateMax ?? ''; ?>" placeholder="jj/mm/aaaa" />

				<input type="submit" class="type1" value="Appliquer" />

			</form>


			<?php
    // Requetes en fonction des paramètres passés
    // par défaut :
    if (date('m') < 9) {
        $dateMin = date('d/m/Y', mktime(0, 0, 0, 9, 1, date('Y') - 1));
    } // départ au premier septebmre de l'année d'avant
    else {
        $dateMin = date('d/m/Y', mktime(0, 0, 0, 9, 1, date('Y')));
    } // départ au premier septebmre cette année
    if (date('m') < 9) {
        $dateMax = date('d/m/Y', mktime(0, 0, 0, 9, 1, date('Y')));
    } // fin au premier septebmre de Cette année
    else {
        $dateMax = date('d/m/Y', mktime(0, 0, 0, 9, 1, date('Y') + 1));
    } // fin au premier septebmre de l'année prochaine

    // recus :
    if (preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $_GET['dateMin'] ?? '')) {
        $dateMin = $_GET['dateMin'];
    }
    if (preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $_GET['dateMax'] ?? '')) {
        $dateMax = $_GET['dateMax'];
    }

    // conversion tsp
    $tspMin = strtotime(str_replace('/', '-', $dateMin));
    $tspMax = strtotime(str_replace('/', '-', $dateMax) . ' 23:59');

    /*** USERS **/

    if ('users' == $p2 && isset($key)) {
        $comTab[$key]['stats'] = [];

        foreach ($comTab as $key => $comm) {
            // Nombre de participations demandées à des sorties pas annulées
            // NOTE : temps = de la sortie et pas de la réza
            $req = 'SELECT COUNT(id_evt_join)
					FROM caf_evt, caf_evt_join
					WHERE evt_evt_join = id_evt
					AND cancelled_evt = 0
					AND commission_evt =' . (int) $comm['id_commission'] . "

					AND tsp_evt > $tspMin
					AND tsp_evt < $tspMax
					";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_total'] = $row[0];

            // *** dont hommes acceptés
            $req = 'SELECT COUNT(id_evt_join)
                    FROM caf_evt, caf_evt_join, caf_user
                    WHERE evt_evt_join = id_evt
                    AND cancelled_evt = 0
                    AND status_evt_join = 1
                    AND commission_evt =' . (int) $comm['id_commission'] . "

                    AND tsp_evt > $tspMin
                    AND tsp_evt < $tspMax

                    AND id_user = user_evt_join
                    AND civ_user LIKE 'M'
                    ";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_total_hommes'] = $row[0];

            // *** dont femmes acceptées
            $req = 'SELECT COUNT(id_evt_join)
                    FROM caf_evt, caf_evt_join, caf_user
                    WHERE evt_evt_join = id_evt
                    AND cancelled_evt = 0
                    AND status_evt_join = 1
                    AND commission_evt =' . (int) $comm['id_commission'] . "

                    AND tsp_evt > $tspMin
                    AND tsp_evt < $tspMax

                    AND id_user = user_evt_join
                    AND civ_user NOT LIKE 'M'
                    ";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_total_femmes'] = $row[0];

            // *** dont mineurs acceptés (a la date de sortie)
            $req = 'SELECT COUNT(id_evt_join)
                    FROM caf_evt, caf_evt_join, caf_user
                    WHERE evt_evt_join = id_evt
                    AND cancelled_evt = 0
                    AND status_evt_join = 1
                    AND commission_evt =' . (int) $comm['id_commission'] . "

                    AND tsp_evt > $tspMin
                    AND tsp_evt < $tspMax

                    AND id_user = user_evt_join
                    AND birthday_user > (tsp_evt - " . (18 * 365 * 24 * 60 * 60) . ')
                    ';
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_total_mineurs'] = $row[0];

            // Nombre de participations confirmés
            // NOTE : temps = de la sortie et pas de la réza
            $req = 'SELECT COUNT(id_evt_join)
					FROM caf_evt, caf_evt_join
					WHERE evt_evt_join = id_evt
					AND status_evt_join = 1
					AND commission_evt =' . (int) $comm['id_commission'] . "
					AND cancelled_evt = 0

					AND tsp_evt > $tspMin
					AND tsp_evt < $tspMax
					";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_1'] = $row[0];

            // Nombre de participations refusés
            // NOTE : temps = de la sortie et pas de la réza
            $req = 'SELECT COUNT(id_evt_join)
					FROM caf_evt, caf_evt_join
					WHERE evt_evt_join = id_evt
					AND status_evt_join = 2
					AND commission_evt =' . (int) $comm['id_commission'] . "
					AND cancelled_evt = 0

					AND tsp_evt > $tspMin
					AND tsp_evt < $tspMax
					";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_2'] = $row[0];

            // Nombre de participations absents
            // NOTE : temps = de la sortie et pas de la réza
            $req = 'SELECT COUNT(id_evt_join)
					FROM caf_evt, caf_evt_join
					WHERE evt_evt_join = id_evt
					AND status_evt_join = 3
					AND commission_evt =' . (int) $comm['id_commission'] . "
					AND cancelled_evt = 0

					AND tsp_evt > $tspMin
					AND tsp_evt < $tspMax
					";

            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['join_3'] = $row[0];
        } ?>

				<br />
				<!-- AFFICHAGE DU TABLEAU -->
				<script type="text/javascript">
				$(document).ready(function() {
					$('#statsUsers').dataTable( {
						"iDisplayLength": 100,
						"aaSorting": [[ 2, "desc" ]],
						"sDom": 'T<"clear">lfrtip',
						"oTableTools": {
							"sSwfPath": "/tools/datatables/extras/TableTools/media/swf/copy_csv_xls_pdf.swf",
							"aButtons": [
								"copy",
								"csv",
								"xls",
								{
									"sExtends": "pdf",
									"sPdfOrientation": "landscape"
									// "sPdfMessage": "Your custom message would go here."
								},
								"print"
							]
						}
					} );
					$('span.br').html('<br />');
				});
				</script>


				<br />
				<table id="statsUsers" class="datatables ">
					<thead>
						<tr>
							<th>Commission</th>
							<th>Demandes d'inscriptions totales</th>
							<th>Participants acceptés</th>
							<th>Participants refusées</th>
							<th>Hommes acceptés</th>
							<th>Femmes acceptées</th>
							<th>% hommes / femmes</th>
							<th>Total mineurs</th>
							<th>% mineurs</th>
							<th>Absents</th>
						</tr>
					</thead>
					<tbody>
						<?php
                foreach ($comTab as $key => $comm) {
                    echo '<tr id="tr-' . $comm['id_commission'] . '" class="' . ($comm['vis_commission'] ? 'vis-on' : 'vis-off') . '">'
                        . '<td>' . html_utf8($comm['title_commission']) . '</td>'

                        . '<td>' . (int) $comm['stats']['join_total'] . '</td>'
                        . '<td>' . (int) $comm['stats']['join_1'] . '</td>'
                        . '<td>' . (int) $comm['stats']['join_2'] . '</td>'
                        . '<td>' . (int) $comm['stats']['join_total_hommes'] . '</td>'
                        . '<td>' . (int) $comm['stats']['join_total_femmes'] . '</td>'
                        . '<td>' . ($comm['stats']['join_total'] > 0 ? (int) ($comm['stats']['join_total_hommes'] * 100 / $comm['stats']['join_1']) : '0') . '</td>'

                        . '<td>' . (int) $comm['stats']['join_total_mineurs'] . '</td>'
                        . '<td>' . ($comm['stats']['join_total'] > 0 ? (int) ($comm['stats']['join_total_mineurs'] * 100 / $comm['stats']['join_1']) : '0') . '</td>'

                        . '<td>' . (int) $comm['stats']['join_3'] . '</td>'
                    . '</tr>' . "\n";
                } ?>
					</tbody>
				</table>

				<?php
    }

    /*** COMMISSIONS **/

    if ('commissions' == $p2) {
        foreach ($comTab as $key => $comm) {
            $comTab[$key]['stats'] = [];

            // SORTIES
            $req = 'SELECT COUNT(id_evt)
						FROM caf_evt
						WHERE commission_evt=' . $comm['id_commission'] . "
						AND tsp_evt > $tspMin
						AND tsp_evt < $tspMax
						";

            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['evt_total'] = $row[0];

            // SORTIES VALIDEES
            $req = 'SELECT COUNT(id_evt)
						FROM caf_evt
						WHERE commission_evt=' . $comm['id_commission'] . "
						AND status_evt = 1
						AND tsp_evt > $tspMin
						AND tsp_evt < $tspMax
						";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['evt_1'] = $row[0];

            // SORTIES REFUSÉES
            $req = 'SELECT COUNT(id_evt)
						FROM caf_evt
						WHERE commission_evt=' . $comm['id_commission'] . "
						AND status_evt = 2
						AND tsp_evt > $tspMin
						AND tsp_evt < $tspMax
						";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['evt_2'] = $row[0];

            // SORTIES VALIDEES
            $req = 'SELECT COUNT(id_evt)
						FROM caf_evt
						WHERE commission_evt=' . $comm['id_commission'] . "
						AND status_legal_evt = 1
						AND tsp_evt > $tspMin
						AND tsp_evt < $tspMax
						";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['evt_legal_1'] = $row[0];

            // SORTIES NON VALIDEES
            $req = 'SELECT COUNT(id_evt)
						FROM caf_evt
						WHERE commission_evt=' . $comm['id_commission'] . "
						AND status_legal_evt = 0
						AND tsp_evt > $tspMin
						AND tsp_evt < $tspMax
						";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['evt_legal_0'] = $row[0];

            // NOMBRE DE RESPONSABLES DE COMMISSION
            $req = "SELECT COUNT(id_user )
						FROM caf_user, caf_usertype, caf_user_attr
						WHERE id_user = user_user_attr
						AND params_user_attr LIKE 'commission:" . $key . "'
						AND usertype_user_attr LIKE id_usertype
						AND code_usertype LIKE 'responsable-commission'
						AND doit_renouveler_user = 0
						";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['respcomm'] = $row[0];

            // NOMBRE D'ENCADRANTS
            $req = "SELECT COUNT( id_user )
						FROM caf_user, caf_usertype, caf_user_attr
						WHERE id_user = user_user_attr
						AND params_user_attr LIKE 'commission:" . $key . "'
						AND usertype_user_attr LIKE id_usertype
						AND (code_usertype LIKE 'encadrant' OR code_usertype LIKE 'stagiaire')
						AND doit_renouveler_user = 0
						";

            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['encadrants'] = $row[0];

            // NOMBRE DE COENCADRANTS
            $req = "SELECT COUNT( id_user )
						FROM caf_user, caf_usertype, caf_user_attr
						WHERE id_user = user_user_attr
						AND params_user_attr LIKE 'commission:" . $key . "'
						AND usertype_user_attr LIKE id_usertype
						AND code_usertype LIKE 'coencadrant'
						AND doit_renouveler_user = 0
						";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $row = $result->fetch_row();
            $comTab[$key]['stats']['coencadrants'] = $row[0];
        } ?>

				<br />
				<!-- AFFICHAGE DU TABLEAU -->

				<script type="text/javascript">
				$(document).ready(function() {
					$('#statsCommissions').dataTable( {
						"iDisplayLength": 100,
						"aaSorting": [[ 2, "desc" ], [ 4, "asc" ]],
						"sDom": 'T<"clear">lfrtip',
						"oTableTools": {
							"sSwfPath": "/tools/datatables/extras/TableTools/media/swf/copy_csv_xls_pdf.swf",
							"aButtons": [
								"copy",
								"csv",
								"xls",
								{
									"sExtends": "pdf",
									"sPdfOrientation": "landscape"
									// "sPdfMessage": "Your custom message would go here."
								},
								"print"
							]
						}
					} );
					$('span.br').html('<br />');
				});
				</script>


				<br />
				<table id="statsCommissions" class="datatables ">
					<thead>
						<tr>
							<th>Commission</th>
							<th>Sorties proposées</th>
							<th>Sorties publiées</th>
							<th>Sorties refusées</th>
							<th>Sorties validées (président)</th>
							<th>Sorties non validées</th>
							<th>Responsables de commission</th>
							<th>Encadrants</th>
							<th>Co-Encadrants</th>
						</tr>
					</thead>
					<tbody>
						<?php
                foreach ($comTab as $key => $comm) {
                    echo '<tr id="tr-' . $comm['id_commission'] . '" class="' . ($comm['vis_commission'] ? 'vis-on' : 'vis-off') . '">'
                        . '<td>' . html_utf8($comm['title_commission']) . '</td>'
                        . '<td>' . (int) $comm['stats']['evt_total'] . '</td>'
                        . '<td>' . (int) $comm['stats']['evt_1'] . '</td>'
                        . '<td>' . (int) $comm['stats']['evt_2'] . '</td>'
                        . '<td>' . (int) $comm['stats']['evt_legal_1'] . '</td>'
                        . '<td>' . (int) $comm['stats']['evt_legal_0'] . '</td>'
                        . '<td>' . (int) $comm['stats']['respcomm'] . '</td>'
                        . '<td>' . (int) $comm['stats']['encadrants'] . '</td>'
                        . '<td>' . (int) $comm['stats']['coencadrants'] . '</td>'
                    . '</tr>' . "\n";
                } ?>
					</tbody>
				</table>

				<?php
    }
} elseif (allowed('article_create') && 'nbvues' == $p2) {
    ?>
				<br />
				<!-- AFFICHAGE DU TABLEAU DES ARTICLES -->
				<link rel="stylesheet" href="/tools/datatables/extras/TableTools/media/css/TableTools.css" type="text/css" media="screen" />
				<script type="text/javascript" src="/tools/datatables/extras/TableTools/media/js/TableTools.min.js"></script>

				<script type="text/javascript">
				$(document).ready(function() {
					$('#statsArticles').dataTable( {
						"iDisplayLength": 100,
						"aaSorting": [[ 5, "desc" ]],
						"sDom": 'T<"clear">lfrtip',
						"oTableTools": {
							"sSwfPath": "/tools/datatables/extras/TableTools/media/swf/copy_csv_xls_pdf.swf",
							"aButtons": [
								"copy",
								"csv",
								"xls",
								{
									"sExtends": "pdf",
									"sPdfOrientation": "landscape"
									// "sPdfMessage": "Your custom message would go here."
								},
								"print"
							]
						}
					} );
					$('span.br').html('<br />');
				});
				</script>

	<?php

            $req = 'SELECT parent_comment, COUNT(*) AS com_count
						FROM caf_comment
						WHERE status_comment=1
						GROUP BY parent_comment;
						';

    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $comments = [];
    while ($comment = $result->fetch_assoc()) {
        $comments[$comment['parent_comment']] = $comment['com_count'];
    }

    $req = 'SELECT * FROM caf_article
						LEFT JOIN caf_commission ON (caf_commission.id_commission = caf_article.commission_article)
						LEFT JOIN caf_user ON (caf_user.id_user = caf_article.user_article)
						ORDER BY nb_vues_article DESC;
						';

    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req); ?>

					<br />
					<table id="statsArticles" class="datatables ">
					<thead>
						<tr>
							<th>Date de<br />publication</th>
							<th>Titre</th>
							<th>Rédacteur</th>
							<th>Commission</th>
							<th>Nombre de<br />commentaires</th>
							<th>Nombre de vues</th>
						</tr>
					</thead>
					<tbody>
			<?php
            while ($article = $result->fetch_assoc()) {
                echo '<tr id="tr-' . $article['id_article'] . '" class="vis-on">'
                . '<td>' . date('d.m.Y', $article['tsp_validate_article']) . '</td>'
                . '<td><a href="' . LegacyContainer::get('legacy_router')->generate('article_view', ['code' => html_utf8($article['code_article']), 'id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL) . '" target="_blank">' . $article['titre_article'] . '</a></td>'
                . '<td>' . userlink($article['id_user'], ucfirst(mb_strtolower($article['firstname_user'], 'UTF-8')) . ' ' . $article['lastname_user']) . '</td>'
                . '<td>' . html_utf8($article['title_commission']) . '</td>'
                . '<td>';
                echo $comments[$article['id_article']] ?? '0';
                echo '</td><td';
                if (1 == $article['une_article']) {
                    echo ' style="background:url(/img/base/star.png) no-repeat center right"';
                }
                echo '>' . (int) $article['nb_vues_article'] . '</td>'
                . '</tr>';
            } ?>
					</tbody>
					</table>

			<?php
} else {
    echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour accéder à cette page</p>';
}

?>

		<br style="clear:both" />
	</div>
</div>
