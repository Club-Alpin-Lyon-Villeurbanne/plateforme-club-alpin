<?php

use App\Legacy\LegacyContainer;

if (user()) {
    $idEvt = isset($_GET['id_evt']) ? (int) $_GET['id_evt'] : 0;
    $idUser = $_POST['id_user'] ?? null;
    $show = isset($_GET['show']) ? $_GET['show'] : 'valid';
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);
    $versCettePage = htmlspecialchars($versCettePage, \ENT_QUOTES, 'UTF-8');

    if (!allowed('evt_join_notme')) {
        echo '<p class="erreur">Vous n\'avez pas les droits requis pour afficher cette page</p>';
    } elseif (!$idEvt) {
        echo '<p class="erreur">ID de sortie non spécifié</p>';
    } else {
        ?>
		<h1>Inscrire manuellement des adhérents à cette sortie</h1>

		<?php
        if (!is_array($idUser)) {
            ?>
			<p>
                <a href="<?php echo $versCettePage; ?>?id_evt=<?php echo $idEvt; ?>&show=valid"
                    class="boutonFancy"
                    <?php if ('valid' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                    ✔️ Licence valide
                </a>&nbsp;

                <a href="<?php echo $versCettePage; ?>?id_evt=<?php echo $idEvt; ?>&show=all"
                    class="boutonFancy"
                    <?php if ('all' === $show) { ?>style="background:#d3d6ff"<?php } ?>>
                    📋 Tous les adhérents
                </a>
			</p>
			<br />

			<link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.sobre.css" type="text/css" media="screen" />
			<script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>

			<style type="text/css">
				tr{			cursor:pointer;	color:gray; background-image:url(img/label-down.png) top left;	}
				tr.up{		color:black; text-shadow: -1px 0 0px white; outline:1px solid silver;	}
				tr:hover{	outline:1px solid silver;	}
				tr input{	display:none;	}
				tr .tick{	display:none;	}
				tr .cross{	display:block;	}
				tr.up .tick{	display:block;	}
				tr.up .cross{	display:none;	}
			</style>

			<script type="text/javascript">
			$(document).ready(function() {
				$('.datatables').dataTable({
					"iDisplayLength": 9000,
					"aLengthMenu": [[-1], ["Tout"]],
                    "aaSorting": [
                        [3, "asc"],
                        [4, "asc"]
                    ],
					"sDom": 'T<"clear">lfrtip'
				});

				$('tr').live('click', function(){
                    const checkbox = $(this).find('input[type=checkbox]');
                    checkbox.attr('checked', !checkbox.attr('checked'));
					if(checkbox.attr('checked'))	$(this).find('input[type=hidden]').removeAttr('disabled');
					else							$(this).find('input[type=hidden]').attr('disabled', 'disabled');
					$(this).toggleClass('up');
					return false;
				});
			});
			</script>

			<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
                <?php
                $baseReq = 'SELECT id_user, email_user, cafnum_user, firstname_user, lastname_user, nickname_user,
                                created_user, birthday_user, tel_user, tel2_user, civ_user, valid_user
                        FROM `caf_user`
                        WHERE id_user NOT IN (SELECT user_evt_join FROM `caf_evt_join` WHERE evt_evt_join=' . $idEvt . ')';

            switch ($show) {
                case 'valid':
                    $req = $baseReq . ' AND doit_renouveler_user=0 AND nomade_user=0';
                    break;
                case 'all':
                default:
                    $req = $baseReq;
                    break;
            }

            $req .= ' ORDER BY lastname_user ASC, firstname_user ASC LIMIT 9000';
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            ?>

				<table class="datatables" style="width:100%;">
					<thead>
						<tr>
							<th></th>
							<th>n° licence FFCAM</th>
							<th>Civ</th>
							<th>Nom</th>
							<th>Prénom</th>
							<th>Pseudo</th>
                            <th>Age</th>
                            <th>Compte activé ?</th>
						</tr>
					</thead>
					<tbody>
						<?php
                        while ($elt = $result->fetch_assoc()) {
                            echo '<tr id="tr-' . $elt['id_user'] . '" class="' . ($elt['valid_user'] ? 'vis-on' : 'vis-off') . '">'
                                . '<td>'
                                    . '<img src="/img/label-up.png" class="tick" alt="CHECKED" title="" />'
                                    . '<img src="/img/label-down.png" class="cross" alt="OFF" title="" />'
                                    . '<input type="checkbox" name="id_user[]" value="' . (int) $elt['id_user'] . '" />'
                                    . '<input type="hidden" disabled="disabled" name="civ_user[]" value="' . html_utf8($elt['civ_user']) . '" />'
                                    . '<input type="hidden" disabled="disabled" name="lastname_user[]" value="' . html_utf8($elt['lastname_user']) . '" />'
                                    . '<input type="hidden" disabled="disabled" name="firstname_user[]" value="' . html_utf8($elt['firstname_user']) . '" />'
                                    . '<input type="hidden" disabled="disabled" name="nickname_user[]" value="' . html_utf8($elt['nickname_user']) . '" />'
                                . '</td>'
                                . '<td>'
                                    . html_utf8($elt['cafnum_user'])
                                . '</td>'
                                . '<td>' . html_utf8($elt['civ_user']) . '</td>'
                                . '<td>' . strtoupper(html_utf8($elt['lastname_user'])) . '</td>'
                                . '<td>' . ucfirst(html_utf8($elt['firstname_user'])) . '</td>'
                                . '<td>' . userlink($elt['id_user'], $elt['nickname_user']) . '</td>'
                                . '<td>' . getYearsSinceDate($elt['birthday_user']) . '</td>'
                                . '<td>' . ($elt['valid_user'] ? 'oui' : '<span style="color: red;" title="Les comptes non activés ne reçoivent pas les e-mails">⚠️ non</span>') . '</td>'
                            . '</tr>';
                        } ?>
					</tbody>
				</table>
				<br style="clear:both" />
				<br />
				<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
					<span class="bleucaf">&gt;</span>
					Étape suivante : attribuer les rôles
				</a>
			</form>

			<?php
        } else {
            if (!count($idUser)) {
                if (isset($_POST['result']) && 'success' == $_POST['result']) {
                    unset($_POST['result']);
                    echo '<p class="erreur">Aucune donnée reçue. <a href="' . $versCettePage . '">Retour</a></p>';
                } else {
                    echo '<p class="info">Inscription effectuée. <a href="' . $versCettePage . '">Retour</a></p>';
                }
            } else {
                $req = 'SELECT * FROM `caf_evt` WHERE `id_evt` = ' . $idEvt;
                $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($sorties = $result->fetch_assoc()) {
                    $sortie = $sorties;
                } ?>
				<p>
					Choisissez le rôle et les éventuelles options individuelles de chacun, puis validez pour confirmer. Attention : chaque utilisateur recevra un e-mail pour
					être averti de son inscription.<br />
					<a href="<?php echo $versCettePage; ?>?id_evt=<?php echo $idEvt; ?>" title="">&lt; Annuler / retour</a>
				</p>
				<br />

				<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
					<input type="hidden" name="operation" value="user_join_manuel" />
					<?php
                    if (isset($_POST['operation']) && 'user_join_manuel' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                        echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                    }
                if (isset($_POST['operation']) && 'user_join_manuel' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                    ?>
						<p class="info">Envoi effectué. Actualisez cette page pour afficher les modifications.</p>
						<script type="text/javascript">
							top.window.location.href=top.window.location.href;
							top.window.location.reload();
						</script>
						<?php
                } ?>

					<table class="big-lines-table" style="font-size:12px;">
						<thead>
							<th style="font-size:12px; text-align:left">Nom</th>
							<th style="font-size:12px; text-align:left">Pseudo</th>
							<th style="font-size:12px; text-align:left">Rôle</th>
						</thead>
						<tbody>
							<?php
                        foreach ($idUser as $i => $utilisateur) {
                            echo '<tr>'
                                . '<td>'
                                    . '<input type="hidden" name="id_user[]" value="' . (int) $idUser[$i] . '" />'
                                    . '<input type="hidden" name="civ_user[]" value="' . html_utf8(stripslashes($_POST['civ_user'][$i] ?? '')) . '" />'
                                    . '<input type="hidden" name="lastname_user[]" value="' . html_utf8(stripslashes($_POST['lastname_user'][$i] ?? '')) . '" />'
                                    . '<input type="hidden" name="firstname_user[]" value="' . html_utf8(stripslashes($_POST['firstname_user'][$i] ?? '')) . '" />'
                                    . '<input type="hidden" name="nickname_user[]" value="' . html_utf8(stripslashes($_POST['nickname_user'][$i] ?? '')) . '" />'
                                    . html_utf8(stripslashes($_POST['civ_user'][$i] ?? '')) . ' '
                                    . ucfirst(html_utf8(stripslashes($_POST['firstname_user'][$i] ?? ''))) . ' '
                                    . strtoupper(html_utf8(stripslashes($_POST['lastname_user'][$i] ?? ''))) . ' '
                                . '</td>'
                                . '<td>'
                                    . html_utf8(stripslashes($_POST['nickname_user'][$i] ?? ''))
                                . '</td>'
                                . '<td>'
                                    . '<select name="role_evt_join[]">'
                                        . '<option value="manuel">Inscrit (par défaut)</option>'
                                        . (1 == $sortie['need_benevoles_evt'] ? '<option value="benevole">Bénévole</option>' : '')
                                    . '</select>'
                                . '</td>';
                            echo '</tr>';
                        } ?>
						</tbody>
					</table>

					<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
						<span class="bleucaf">&gt;</span>
						CONFIRMER LES INSCRIPTIONS ET ENVOYER LES E-MAILS AUTOMATIQUES
					</a>
				</form>
				<?php
            }
        }
    }
}
