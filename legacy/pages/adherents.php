<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

if (allowed('user_see_all') || isGranted(SecurityConstants::ROLE_ADMIN)) {
    $userTab = [];
    if (allowed('user_see_all')) {
        $show = 'allvalid';
    }
    if (isGranted(SecurityConstants::ROLE_ADMIN)) {
        $show = 'valid';
    }
    // fonctions disponibles
    if (isset($_GET['show']) && in_array($_GET['show'], ['all', 'manual', 'valid', 'nomade', 'dels', 'expired', 'allvalid'], true)) {
        $show = $_GET['show'];
    }
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);

    $req = 'SELECT id_user , email_user , cafnum_user , firstname_user , lastname_user , nickname_user , created_at , updated_at , birthdate , tel_user , tel2_user , adresse_user, cp_user ,  ville_user ,  civ_user , valid_user , manuel_user, nomade_user, join_date, doit_renouveler_user, alerte_renouveler_user
		FROM  `caf_user` WHERE is_deleted=0'
        . ('dels' == $show ? ' AND valid_user=2 ' : '')
        . ('manual' == $show ? ' AND manuel_user=1 ' : '')
        . ('nomade' == $show ? ' AND nomade_user=1 ' : '')
        . ('valid' == $show ? ' AND valid_user=1 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('allvalid' == $show ? ' AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        . ('expired' == $show ? ' AND doit_renouveler_user=1 ' : '')
        . ' ORDER BY lastname_user ASC, firstname_user ASC LIMIT 9000';

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
}
?>

<!-- MAIN -->
<div id="main" role="main" style="width:100%">
	<div style="padding:20px 10px;">
		<?php
        if (!allowed('user_see_all') && !isGranted(SecurityConstants::ROLE_ADMIN)) {
            echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour accéder à cette page</p>';
        } else {
            ?>
            <div>
                <h1>Gestion des adhérents</h1>
                <p>
                    <img src="/img/base/magnifier.png" style="vertical-align:middle" />
                    Le champ "<i>Search</i>" en haut à droite du tableau vous permet de rechercher n'importe quelle valeur instantanément.<br />
                    <?php if (isGranted(SecurityConstants::ROLE_ADMIN)) { ?>
                    <img src="/img/base/database_go.png" style="vertical-align:middle" />
                    Les boutons de droite vous permettent d'exporter le tableau courant, le plus utile étant l'exportation en .csv.<br />
                    <?php } ?>
                    <img src="/img/base/info.png" style="vertical-align:middle" />
                    Vous pouvez trier les résultats selon différents critères en même temps, en pressant la touche <i>Maj / Shift</i> en cliquant sur les titres des colonnes.<br />
                </p>
                <br>

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
                        🌍 Non-adhérents
                    </a>&nbsp;

                    <?php if (isGranted(SecurityConstants::ROLE_ADMIN)) { ?>
                    <a href="/adherents.html?show=manual"
                    class="boutonFancy"
                    <?php if ('manual' == $show) { echo 'style="background:#d3d6ff"'; } ?>>
                        Créés manuellement
                    </a>&nbsp;
                    <a href="/adherents.html?show=dels"
                       class="boutonFancy"
                        <?php if ('dels' == $show) { echo 'style="background:#d3d6ff"'; } ?>>
                        Désactivés manuellement
                    </a>&nbsp;
                    <?php } ?>

                    <a href="/adherents.html?show=all"
                    class="boutonFancy"
                    <?php if ('all' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                        📋 Tous les adhérents (+ long)
                    </a>
                </div>
            </div>
			<!-- AFFICHAGE DU TABLEAU -->
			<br />
			<br />
			<link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.css" type="text/css" media="screen" />
			<script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#pagesLibres').dataTable({
						"iDisplayLength": 100,
						"aaSorting": [
							[2, "asc"],
							[3, "asc"]
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
						<th>n° licence FFCAM / Infos</th>
						<th>Nom</th>
						<th>Prénom</th>
						<th>Adhésion</th>
						<th>Pseudo</th>
						<th>Age</th>
						<th>Tél<?php if (isGranted(SecurityConstants::ROLE_ADMIN)) { ?> / Tél secours<?php } ?></th>
						<th>E-mail</th>
                        <th>Compte activé ?</th>
                        <?php if (isGranted(SecurityConstants::ROLE_ADMIN)) { ?>
                        <th>CP</th>
                        <th>Ville</th>
                        <th>Licence</th>
                        <?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php
            $isAllowed_user_giveright_1 = allowed('user_giveright_1');
            $isAllowed_user_giveright_2 = allowed('user_giveright_2');
            $isAllowed_user_givepresidence = allowed('user_givepresidence');
            $isAllowed_user_desactivate_any = allowed('user_desactivate_any');
            $isAlowed_user_reactivate = allowed('user_reactivate');
            $isAllowed_user_reset = allowed('user_reset');
            $isAllowed_user_edit_notme = allowed('user_edit_notme');
            $isAllowed_user_read_private = allowed('user_read_private');
            $isGranted_role_allowed_to_switch = isGranted('ROLE_ALLOWED_TO_SWITCH');

            $img_lock = '<img src="/img/base/lock_gray.png" alt="caché"  title="Vous devez disposer de droits supérieurs pour afficher cette information" />';

            while ($elt = $handleSql->fetch_assoc()) {
                $age = null;
                if (!empty($elt['birthdate'])) {
                    $birthdate = new \DateTimeImmutable($elt['birthdate']);
                    $age = $birthdate->diff(new \DateTime())->y;
                }
                $elt['birthday_user'] = $age;

                echo '<tr id="tr-' . $elt['id_user'] . '" class="' . ($elt['valid_user'] ? 'vis-on' : 'vis-off') . '">'

                    // OUTILS
                    . '<td style="white-space:nowrap;">';

                // view user
                if (isGranted(SecurityConstants::ROLE_ADMIN)) {
                    echo '<a href="/includer.php?p=pages/adherents-consulter.php&amp;id_user=' . (int) $elt['id_user'] . '" class="fancyframe" title="Consulter cet adhérent"><img src="/img/base/report.png"></a> ';
                }

                // gestion des droits
                if (isGranted(SecurityConstants::ROLE_ADMIN)) {
                    echo '<a href="/includer.php?admin=true&amp;p=pages/admin-users-droits.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Voir / Attribuer des responsabilités à cet utilisateur"><img src="/img/base/user_star.png"></a> ';
                } elseif ($isAllowed_user_giveright_1 || $isAllowed_user_giveright_2 || $isAllowed_user_givepresidence) {
                    echo '<a href="/includer.php?p=pages/adherents-droits.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Voir / Attribuer des responsabilités à cet utilisateur"><img src="/img/base/user_star.png"></a> ';
                }

                // désactiver
                if ($isAllowed_user_desactivate_any && '1' == $elt['valid_user']) {
                    echo '<a href="/includer.php?p=pages/adherents-desactiver.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Désactiver le compte de cet utilisateur"><img src="/img/base/user_unvalidate.png"></a> ';
                }

                // réactiver
                if ($isAlowed_user_reactivate && '2' == $elt['valid_user']) {
                    echo '<a href="/includer.php?p=pages/adherents-reactiver.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Réactiver le compte de cet utilisateur"><img src="/img/base/user_revalidate.png"></a> ';
                }

                // reset user
                if ($isAllowed_user_reset) {
                    echo '<a href="/includer.php?p=pages/adherents-reset.php&amp;id_user=' . (int) $elt['id_user'] . '&amp;nom=' . urlencode($elt['firstname_user'] . ' ' . $elt['lastname_user']) . '" class="fancyframe" title="Remettre à zéro, réinitialiser le compte de cet utilisateur"><img src="/img/base/user_reset.png"></a> ';
                }

                // edit user
                if ($isAllowed_user_edit_notme) {
                    echo '<a href="/includer.php?p=pages/adherents-modifier.php&amp;id_user=' . (int) $elt['id_user'] . '" class="fancyframe" title="Modifier cet adhérent"><img src="/img/base/user_edit.png"></a> ';
                }

                // impersonate user
                if ($isGranted_role_allowed_to_switch) {
                    echo (1 == $elt['valid_user'] && $elt['email_user']) ? ' <a href="/profil.html?_switch_user=' . urlencode($elt['email_user']) . '" title="Impersonifier l\'utilisateur"><img src="/img/base/user_go.png"></a> ' : '';
                }

                echo '</td>';

                $emailCol = '';
                $activatedCol = '';
                if (1 == $elt['valid_user'] && $elt['email_user']) {
                    $emailCol = ($isAllowed_user_read_private ? '<a href="mailto:' . html_utf8($elt['email_user']) . '" title="Contact direct">' . html_utf8($elt['email_user']) . '</a>' : $img_lock);
                    $activatedCol = 'oui';
                } else {
                    $emailCol = '<span style="color: darkorange; font-weight: bold" title="Les comptes non activés ne reçoivent pas les e-mails">⚠️ compte non activé</span>';
                    $activatedCol = '<span style="color: darkorange; font-weight: bold;" title="Les comptes non activés ne reçoivent pas les e-mails">non</span>';
                }

                // INFOS
                echo '<td>'
                    . html_utf8($elt['cafnum_user']) . '<br />'
                    . ($elt['manuel_user'] ? '<img src="/img/base/user_manuel.png" alt="MANUEL" title="Utilisateur créé manuellement" /> ' : '')
                    . ($elt['nomade_user'] ? '<img src="/img/base/nomade_user.png" alt="NOMADE" title="Utilisateur nomade" /> ' : '')
                    . ('2' == $elt['valid_user'] ? '<img src="/img/base/user_desactive.png" alt="DESACTIVE" title="Utilisateur désactivé manuellement" /> ' : '')
                    . '</td>'
                    . '<td>' . html_utf8(strtoupper($elt['lastname_user'])) . '</td>'
                    . '<td>' . html_utf8(ucfirst($elt['firstname_user'])) . '</td>';

                if ($elt['doit_renouveler_user']) {
                    $joinDate = new \DateTimeImmutable($elt['join_date']);
                    echo '<td style="color:red" title="' . ($isAllowed_user_read_private ? (!empty($elt['join_date']) ? $joinDate?->format('d/m/Y') : '') : '') . '">Licence expirée</td>';
                } else {
                    $joinDate = new \DateTimeImmutable($elt['join_date']);
                    echo '<td>' . ($isAllowed_user_read_private ? (!empty($elt['join_date']) ? $joinDate?->format('d/m/Y') : '-') : $img_lock) . '</td>';
                }

                echo '<td>' . userlink($elt['id_user'], $elt['nickname_user']) . '</td>'
                . '<td>' . ($isAllowed_user_read_private ? '<span style="display:none">' . $elt['birthday_user'] . '</span>' . ($elt['birthday_user'] > 0 ? $elt['birthday_user'] . ' ans' : '...') : $img_lock) . '</td>'
                . '<td>' . ($isAllowed_user_read_private ? html_utf8($elt['tel_user']) : $img_lock);
                if (isGranted(SecurityConstants::ROLE_ADMIN)) {
                    echo '<br />' . html_utf8($elt['tel2_user']);
                }
                echo '</td>'
                . '<td>' . $emailCol . '</td>'
                . '<td>' . $activatedCol . '</td>';
                if (isGranted(SecurityConstants::ROLE_ADMIN)) {
                    echo '<td>' . html_utf8($elt['cp_user']) . '</td>'
                        . '<td>' . html_utf8($elt['ville_user']) . '</td>'
                        . '<td>' . ($elt['doit_renouveler_user'] ? 'expirée' : 'valide') . ' ' . (!$elt['doit_renouveler_user'] && isset($elt['alerte_renouveler_user']) && $elt['alerte_renouveler_user'] ? '<span style="color:red">* Doit renouveler</span>' : '') . '</td>';
                }
                echo '</tr>';
            } ?>
				</tbody>
			</table>

		<?php
        }
?>
		<br style="clear:both" />
        <br style="clear:both" />
	</div>
</div>