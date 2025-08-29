<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
} else {
    $userTab = [];
    $show = 'valid';
    // fonctions disponibles
    if (isset($_GET['show']) && in_array($_GET['show'], ['all', 'manual', 'notvalid', 'nomade', 'dels', 'expired', 'valid-expired'], true)) {
        $show = $_GET['show'];
    }
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);

    $req = 'SELECT id_user , email_user , cafnum_user , firstname_user , lastname_user , nickname_user , created_user , birthday_user , tel_user , tel2_user , adresse_user, cp_user ,  ville_user ,  civ_user , valid_user , manuel_user, nomade_user, date_adhesion_user, doit_renouveler_user
		FROM  `caf_user` WHERE is_deleted=0'
        . ('dels' == $show ? ' AND valid_user=2 ' : '')
        . ('manual' == $show ? ' AND manuel_user=1 ' : '')
        . ('nomade' == $show ? ' AND nomade_user=1 ' : '')
        . ('valid' == $show ? ' AND valid_user=1 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('notvalid' == $show ? ' AND valid_user=0 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('expired' == $show ? ' AND valid_user=0 AND doit_renouveler_user=1 ' : '')
        . ('valid-expired' == $show ? ' AND valid_user=1 AND doit_renouveler_user=1 ' : '')
        . ' ORDER BY lastname_user ASC LIMIT 9000';

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
    ?>
	<h1>Administration des utilisateurs & adhérents</h1>
	<p>
		<img src="/img/base/magnifier.png" style="vertical-align:middle" />
		Le champ "<i>Search</i>" en haut à droite du tableau vous permet de rechercher n'importe quelle valeur instantanément.<br />
		<img src="/img/base/database_go.png" style="vertical-align:middle" />
		Les boutons de droite vous permettent d'exporter le tableau courant, le plus utile étant l'exportation en .csv.<br />
		<img src="/img/base/info.png" style="vertical-align:middle" />
		Vous pouvez trier les résultats selon différents critères en même temps, en pressant la touche <i>Maj / Shift</i> en cliquant sur les titres des colonnes.<br />
	</p>


	<p><strong>Voir les comptes adhérents :</strong>
		<a href="/admin-users.html" <?php if ('valid' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> activé / licence valide </a>&nbsp;
		<a href="/admin-users.html?show=valid-expired" <?php if ('valid-expired' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> activé / licence expirée </a>&nbsp;
		<a href="/admin-users.html?show=notvalid" <?php if ('notvalid' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> non activé / licence valide </a>&nbsp;
		<a href="/admin-users.html?show=expired" <?php if ('expired' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> non activé / licence expirée </a>&nbsp;
		<a href="/admin-users.html?show=dels" <?php if ('dels' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> désactivés manuellement </a>&nbsp;
		<a href="/admin-users.html?show=manual" <?php if ('manual' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> créés manuellement </a>&nbsp;
		<a href="/admin-users.html?show=nomade" <?php if ('nomade' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> nomades </a>&nbsp;
		<a href="/admin-users.html?show=all" <?php if ('all' == $show) {
		    echo 'style="background:#d3d6ff"';
		} ?> class="boutonFancy"> tous (+long) </a>
	</p>


	<!-- AFFICHAGE DU TABLEAU -->
	<br />
	<br />
	<link rel="stylesheet" href="/tools/datatables/extras/TableTools/media/css/TableTools.css" type="text/css" media="screen" />
	<script type="text/javascript" src="/tools/datatables/extras/TableTools/media/js/TableTools.min.js"></script>

	<script type="text/javascript">
		$(document).ready(function() {
			$('#pagesLibres').dataTable({
				"iDisplayLength": 100,
				"aaSorting": [
					[2, "desc"],
					[4, "asc"]
				],
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
			});
			$('span.br').html('<br />');
		});
	</script>


	<br />
	<table id="pagesLibres" class="datatables ">
		<thead>
			<tr>
				<th>Outils</th>
				<th>n° licence FFCAM / Infos / DBID </th>
				<th>Actif ?</th>
				<th>Civ</th>
				<th>Nom</th>
				<th>Prénom</th>
				<th>Adhésion</th>
				<th>Pseudo</th>
				<th>Age</th>
				<th>Tel / Tel2</th>
				<th>E-mail</th>
				<!-- <th>Adresse</th> -->
				<th>CP</th>
				<th>Ville</th>
				<!-- <th>Pays</th> -->
				<th>Licence</th>
				<!--<th>Droits</th>-->
			</tr>
		</thead>
		<tbody>
			<?php
		    $total = 0;

    for ($i = 0; $i < count($userTab); ++$i) {
        $elt = $userTab[$i];

        echo '<tr id="tr-' . $elt['id_user'] . '" class="' . ($elt['valid_user'] ? 'vis-on' : 'vis-off') . '">'
            . '<td style="white-space:nowrap;">'

            // view user
            . '<a href="/includer.php?p=pages/adherents-consulter.php&amp;id_user=' . (int) $elt['id_user'] . '" class="fancyframe" title="Consulter cet adhérent"><img src="/img/base/report.png"></a> '

            // gestion des droits
            . '<a href="/includer.php?admin=true&amp;p=pages/admin-users-droits.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Voir / Attribuer des statuts à cet utilisateur"><img src="/img/base/user_star.png"></a> ';

        // désactiver
        if (allowed('user_desactivate_any') && '1' == $elt['valid_user']) {
            echo '<a href="/includer.php?p=pages/adherents-desactiver.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Désactiver le compte de cet utilisateur"><img src="/img/base/user_unvalidate.png"></a> ';
        }

        // réactiver
        if (allowed('user_reactivate') && '2' == $elt['valid_user']) {
            echo '<a href="/includer.php?p=pages/adherents-reactiver.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Réactiver le compte de cet utilisateur"><img src="/img/base/user_revalidate.png"></a> ';
        }

        // reset user
        if (allowed('user_reset')) {
            echo '<a href="/includer.php?p=pages/adherents-reset.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Remettre à zéro, réinitialiser le compte de cet utilisateur"><img src="/img/base/user_reset.png"></a> ';
        }

        // edit user
        if (allowed('user_edit_notme')) {
            echo '<a href="/includer.php?p=pages/adherents-modifier.php&amp;id_user=' . (int) $elt['id_user'] . '" class="fancyframe" title="Modifier cet adhérent"><img src="/img/base/user_edit.png"></a> ';
        }

        if (isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            echo (1 == $elt['valid_user'] && $elt['email_user']) ? ' <a href="/profil.html?_switch_user=' . urlencode($elt['email_user']) . '" title="Impersonifier l\'utilisateur"><img src="/img/base/user_go.png"></a> ' : ' ';
        }

        echo '</td>'
            . '<td>'
            . html_utf8($elt['cafnum_user']) . '<br />'
            . ($elt['manuel_user'] ? '<img src="/img/base/user_manuel.png" alt="MANUEL" title="Utilisateur créé manuellement" /> ' : '')
            . ($elt['nomade_user'] ? '<img src="/img/base/nomade_user.png" alt="NOMADE" title="Utilisateur nomade" /> ' : '')
            . ('2' == $elt['valid_user'] ? '<img src="/img/base/user_desactive.png" alt="DESACTIVE" title="Utilisateur désactivé manuellement" /> ' : '')
            . (int) $elt['id_user'] . ' '
            . '</td>'
            . '<td>' . (int) $elt['valid_user'] . '</td>'
            . '<td>' . html_utf8($elt['civ_user']) . '</td>'
            . '<td>' . strtoupper(html_utf8($elt['lastname_user'])) . '</td>'
            . '<td>' . ucfirst(html_utf8($elt['firstname_user'])) . '</td>';

        if ($elt['doit_renouveler_user']) {
            echo '<td style="color:red">Licence expirée</td>';
        } else {
            echo '<td>' . ($elt['date_adhesion_user'] ? date('Y-m-d', $elt['date_adhesion_user']) : '-') . '</td>';
        }
        echo '<td>' . userlink($elt['id_user'], $elt['nickname_user']) . '</td>'
        . '<td><span style="display:none">' . $elt['birthday_user'] . '</span>' . ($elt['birthday_user'] ? (int) ($elt['birthday_user']) . ' ans' : '...') . '</td>'
        . '<td>' . html_utf8($elt['tel_user']) . '<br />' . html_utf8($elt['tel2_user']) . '</td>'
        . '<td><a href="mailto:' . html_utf8($elt['email_user']) . '" title="Contact direct">' . html_utf8($elt['email_user']) . '</a></td>'
        // .'<td>'.nl2br(html_utf8($elt['adresse_user'])).'</td>'
        . '<td>' . html_utf8($elt['cp_user']) . '</td>'
        . '<td>' . html_utf8($elt['ville_user']) . '</td>'
        // .'<td>'.html_utf8($elt['pays_user']).'</td>'
        . '<td>' . ($elt['doit_renouveler_user'] ? 'expirée' : 'valide') . ' ' . (!$elt['doit_renouveler_user'] && isset($elt['alerte_renouveler_user']) && $elt['alerte_renouveler_user'] ? '<span style="color:red">* Doit renouveler</span>' : '') . '</td>'
        // .'<td></td>'
        . '</tr>';
    } ?>
		</tbody>
	</table>

	<br style="clear:both" />
	<br style="clear:both" />
<?php
}
?>