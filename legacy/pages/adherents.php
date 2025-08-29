<?php

use App\Legacy\LegacyContainer;

if (allowed('user_see_all')) {
    $userTab = [];
    $show = 'allvalid';
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
        . ('allvalid' == $show ? ' AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('notvalid' == $show ? ' AND valid_user=0 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('expired' == $show ? ' AND doit_renouveler_user=1 ' : '')
        . ('valid-expired' == $show ? ' AND valid_user=1 AND doit_renouveler_user=1 ' : '')
        . ' ORDER BY lastname_user ASC, firstname_user ASC LIMIT 9000';			// , pays_user

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
<div id="main" role="main" style="width:100%">
	<div style="padding:20px 10px;">
		<?php
        if (!allowed('user_see_all')) {
            echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour accéder à cette page</p>';
        } else {
            ?>
            <div>
                <h2>Gestion des adhérents</h2>
                <h3>Afficher les adhérents par statut :</h3>
                <div>

                    <a href="/adherents.html"
                    class="boutonFancy"
                    <?php if ('allvalid' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                        ✔️ Licence valide
                    </a>&nbsp;

                    <a href="/adherents.html?show=expired"
                    class="boutonFancy"
                    <?php if ('expired' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                        📅 Licence expirée
                    </a>&nbsp;

                    <a href="/adherents.html?show=nomade"
                    class="boutonFancy"
                    <?php if ('nomade' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                        🌍 Nomades
                    </a>&nbsp;

                    <a href="/adherents.html?show=all"
                    class="boutonFancy"
                    <?php if ('all' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                        📋 Tous les adhérents
                    </a>
                </div>
            </div>
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
							[3, "asc"],
							[4, "asc"]
						]
					});
					$('span.br').html('<br />');
				});
			</script>


			<br />
			<table id="pagesLibres" class="datatables ">
				<thead>
					<tr>
						<th>Outils</th>
						<th>n° CAF / Infos</th>
						<!-- <th>Actif ?</th> -->
						<th>Civ</th>
						<th>Nom</th>
						<th>Prénom</th>
						<th>Adhésion</th>
						<th>Pseudo</th>
						<th>Age</th>
						<th>Tel</th>
						<th>E-mail</th>
					</tr>
				</thead>
				<tbody>
					<?php
                    $total = 0;

            $isAllowed_user_giveright_1 = allowed('user_giveright_1');
            $isAllowed_user_giveright_2 = allowed('user_giveright_2');
            $isAllowed_user_givepresidence = allowed('user_givepresidence');
            $isAllowed_user_desactivate_any = allowed('user_desactivate_any');
            $isAlowed_user_reactivate = allowed('user_reactivate');
            $isAllowed_user_reset = allowed('user_reset');
            $isAllowed_user_edit_notme = allowed('user_edit_notme');
            $isAllowed_user_read_private = allowed('user_read_private');
            $isGranted_role_allowed_to_switch = isGranted('ROLE_ALLOWED_TO_SWITCH');

            for ($i = 0; $i < count($userTab); ++$i) {
                $elt = $userTab[$i];

                echo '<tr id="tr-' . $elt['id_user'] . '" class="' . ($elt['valid_user'] ? 'vis-on' : 'vis-off') . '">'

                    // OUTILS
                    . '<td style="white-space:nowrap;">';
                // seulement ceux valides
                //								if($elt['valid_user']){

                // gestion des droits
                if ($isAllowed_user_giveright_1 || $isAllowed_user_giveright_2 || $isAllowed_user_givepresidence) {
                    echo '<a href="/includer.php?p=pages/adherents-droits.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Voir / Attribuer des responsabilités à cet utilisateur"><img src="/img/base/user_star.png"></a> ';
                }

                // désactiver
                if ($isAllowed_user_desactivate_any && '1' == $elt['valid_user']) {
                    echo '<a href="/includer.php?p=pages/adherents-desactiver.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Désactiver le compte de cet utilisateur"><img src="/img/base/user_unvalidate.png"></a> ';
                }
                // réactiver
                if ($isAlowed_user_reactivate && '2' == $elt['valid_user']) {
                    echo '<a href="/includer.php?p=pages/adherents-reactiver.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Réactiver le compte de cet utilisateur"><img src="/img/base/user_revalidate.png"></a> ';
                }

                // reset user
                if ($isAllowed_user_reset) {
                    echo '<a href="/includer.php?p=pages/adherents-reset.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['civ_user'] . ' ' . $elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Remettre à zéro, réinitialiser le compte de cet utilisateur"><img src="/img/base/user_reset.png"></a> ';
                }

                // edit user
                if ($isAllowed_user_edit_notme) {
                    echo '<a href="/includer.php?p=pages/adherents-modifier.php&amp;id_user=' . (int) $elt['id_user'] . '" class="fancyframe" title="Modifier cet adhérent"><img src="/img/base/user_edit.png"></a> ';
                }

                if ($isGranted_role_allowed_to_switch) {
                    echo (1 == $elt['valid_user'] && $elt['email_user']) ? ' <a href="/profil.html?_switch_user=' . urlencode($elt['email_user']) . '" title="Impersonifier l\'utilisateur"><img src="/img/base/user_go.png"></a> ' : '';
                }

                echo '</td>';

                $img_lock = '<img src="/img/base/lock_gray.png" alt="caché"  title="Vous devez disposer de droits supérieurs pour afficher cette information" />';

                $emailCol = '';
                if (1 == $elt['valid_user'] && $elt['email_user']) {
                    $emailCol = ($isAllowed_user_read_private ? '<a href="mailto:' . html_utf8($elt['email_user']) . '" title="Contact direct">' . html_utf8($elt['email_user']) . '</a>' : $img_lock);
                } else {
                    $emailCol = '<span style="color: red;" title="Les comptes non activés ne reçoivent pas les e-mails">⚠️ compte non activé</span>';
                }

                // INFOS
                echo '<td>'
                    . html_utf8($elt['cafnum_user']) . ' '
                    . ($elt['manuel_user'] ? '<img src="/img/base/user_manuel.png" alt="MANUEL" title="Utilisateur créé manuellement" /> ' : '')
                    . ($elt['nomade_user'] ? '<img src="/img/base/nomade_user.png" alt="NOMADE" title="Utilisateur nomade" /> ' : '')
                    . ('2' == $elt['valid_user'] ? '<img src="/img/base/user_desactive.png" alt="DESACTIVE" title="Utilisateur désactivé manuellement" /> ' : '')
                    . '</td>'
                    // .'<td>'.intval($elt['valid_user']).'</td>'
                    . '<td>' . html_utf8($elt['civ_user']) . '</td>'
                    . '<td>' . strtoupper(html_utf8($elt['lastname_user'])) . '</td>'
                    . '<td>' . ucfirst(html_utf8($elt['firstname_user'])) . '</td>';

                if ($elt['doit_renouveler_user']) {
                    echo '<td style="color:red">Licence expirée</td>';
                } else {
                    echo '<td>' . ($isAllowed_user_read_private ? ($elt['date_adhesion_user'] ? date('Y-m-d', $elt['date_adhesion_user']) : '-') : $img_lock) . '</td>';
                }

                echo '<td>' . userlink($elt['id_user'], $elt['nickname_user']) . '</td>'
                . '<td>' . ($isAllowed_user_read_private ? '<span style="display:none">' . $elt['birthday_user'] . '</span>' . ($elt['birthday_user'] ? (int) ($elt['birthday_user']) . ' ans' : '...') : $img_lock) . '</td>'
                . '<td>' . ($isAllowed_user_read_private ? html_utf8($elt['tel_user']) : $img_lock) . '</td>'
                . '<td>' . $emailCol . '</td>'
                . '</tr>';
            } ?>
				</tbody>
			</table>

		<?php
        }
?>
		<br style="clear:both" />
	</div>
</div>