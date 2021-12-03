<?php
// Cette page sert à joindre manuellement un user à une sortie

if (user()) {
    // id de la sortie, pour n'afficher que les adhérents non inscrits
    $id_evt = (int) ($_GET['id_evt']);
    $id_dest = is_sortie_in_destination($id_evt);
    if ($id_dest) {
        $busses = get_bus_destination($id_dest);
    }

    $showAll = (int) ($_GET['showAll']);

    //if(!allowed('user_see_all')){
    if (!allowed('evt_join_notme')) {
        echo '<p class="erreur">Vous n\'avez pas les droits requis pour afficher cette page</p>';
    } elseif (!$id_evt) {
        echo '<p class="erreur">ID de sortie non spécifié</p>';
    } else {
        // la vérification des droits de cet user à cette sortie se fait lors de l'opération finale : SCRIPTS.'operations.php'?>

		<h1>Inscrire manuellement des adhérents à cette sortie</h1>

		<?php
        // PREMIERE ETAPE : SELECTION DES ADHERENTS A AJOUTER
        if (!is_array($_POST['id_user'])) {
            ?>
			<p>
				<img src="/img/base/magnifier.png" style="vertical-align:middle" />
				Le champ "<i>Search</i>" en haut à droite du tableau vous permet de rechercher n'importe quelle valeur instantanément.<br />
				Cliquez sur le bouton "<i>Inscrire les adhérents sélectionnés</i>" pour passer à l'étape suivante et sélectionner leur rôls éventuels (simple inscrit, bénévole...).
				<br />
				<a href="<?php echo $versCettePage; ?>" <?php if (!$showAll) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy">Voir uniquement les adhérents validés</a>
				<a href="<?php echo $versCettePage; ?>&showAll=1" <?php if ($showAll) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy">Voir tous les adhérents de la base (+long)</a>

			</p>
			<br />

			<!-- AFFICHAGE DU TABLEAU -->
			<!-- DATATABLES -->
			<link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.sobre.css" type="text/css" media="screen" />
			<script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>

			<!-- petit bout de css pour l'ergonomie -->
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

				// SETTING DATATABLES
				$('.datatables').dataTable( {
					"iDisplayLength": 10,
					"aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tout"]],
					"aaSorting": [[ 3, "asc" ]],
					"sDom": 'T<"clear">lfrtip'
				} );

				// SÉLECTIONNER UNE LIGNE AU CLIC
				// $('tr').bind('click', function(){
				$('tr').live('click', function(){
					checkbox=$(this).find('input[type=checkbox]');
					checkbox.attr('checked', !checkbox.attr('checked'));
					// remove / retrieve disabled
					if(checkbox.attr('checked'))	$(this).find('input[type=hidden]').removeAttr('disabled');
					else							$(this).find('input[type=hidden]').attr('disabled', 'disabled');
					// style
					$(this).toggleClass('up');
					return false;
				});

			});
			</script>


			<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
				<!--<input type="hidden" name="operation" value="xxxx" /> not yet -->

				<table class="datatables" style="width:100%;">
					<thead>
						<tr>
							<th></th>
							<th>n° CAF / DBID </th>
							<th>Civ</th>
							<th>Nom</th>
							<th>Prénom</th>
							<th>Pseudo</th>
						</tr>
					</thead>
					<tbody>
						<?php
                        $total = 0;
            // REQ des users validés
            $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
            $userTab = [];
            $req = 'SELECT  id_user, email_user, cafnum_user, firstname_user, lastname_user, nickname_user
									, created_user, birthday_user, tel_user, tel2_user, civ_user
							FROM `caf_user`
							WHERE nomade_user!=1'
                            .($showAll ? '' : ' AND valid_user=1 ')
                            .' ORDER BY lastname_user ASC
							LIMIT 8000';
            $result = $mysqli->query($req);
            while ($elt = $result->fetch_assoc()) {
                // si dans destination :
                if ($id_dest) {
                    // SELECTION : on n'affiche que les adhérents qui ne sont pas inscrit à cette sortie
                    $sorties_ids = get_sorties_for_destination($id_dest, true);
                    $req = "SELECT COUNT(id_evt_join) FROM caf_evt_join WHERE (id_destination=$id_dest OR evt_evt_join IN (".implode(',', $sorties_ids).')) AND user_evt_join = '.(int) ($elt['id_user']).' LIMIT 1';
                }
                //sinon
                else {
                    // SELECTION : on n'affiche que les adhérents qui ne sont pas inscrit à cette sortie
                    $req = "SELECT COUNT(id_evt_join) FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join = ".(int) ($elt['id_user']).' LIMIT 1';
                }

                $result2 = $mysqli->query($req);
                $row = $result2->fetch_row();
                // inscription inexistante
                if (!$row[0]) {
                    echo '<tr id="tr-'.$elt['id_user'].'" class="'.($elt['valid_user'] ? 'vis-on' : 'vis-off').'">'

                                    .'<td>'
                                        .'<img src="/img/label-up.png" class="tick" alt="CHECKED" title="" />'
                                        .'<img src="/img/label-down.png" class="cross" alt="OFF" title="" />'
                                        .'<input type="checkbox" name="id_user[]" value="'.(int) ($elt['id_user']).'" />'
                                        // inputs hidden disabled : activés quand le case est cliquée (jquery)
                                        .'<input type="hidden" disabled="disabled" name="civ_user[]" value="'.html_utf8($elt['civ_user']).'" />'
                                        .'<input type="hidden" disabled="disabled" name="lastname_user[]" value="'.html_utf8($elt['lastname_user']).'" />'
                                        .'<input type="hidden" disabled="disabled" name="firstname_user[]" value="'.html_utf8($elt['firstname_user']).'" />'
                                        .'<input type="hidden" disabled="disabled" name="nickname_user[]" value="'.html_utf8($elt['nickname_user']).'" />'
                                    .'</td>'
                                    .'<td>'
                                        .html_utf8($elt['cafnum_user']).'<br />'
                                        .(int) ($elt['id_user']).' '
                                    .'</td>'
                                    .'<td>'.html_utf8($elt['civ_user']).'</td>'
                                    .'<td>'.html_utf8($elt['lastname_user']).'</td>'
                                    .'<td>'.html_utf8($elt['firstname_user']).'</td>'
                                    .'<td>'.userlink($elt['id_user'], $elt['nickname_user']).'</td>'

                                .'</tr>';
                }
            } ?>
					</tbody>
				</table>
				<br style="clear:both" />
				<br />
				<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
					<span class="bleucaf">&gt;</span>
					ETAPE SUIVANTE : CHOIX DES RÔLES
				</a>
			</form>

			<?php
        }

        // ENSUITE, CONFIRMATION ET ENVOI :
        else {
            // print_r($_POST);
            // print_r($_POST['id_user']);
            // rien de sélectionné
            if (!count($_POST['id_user'])) {
                if ('success' == $_POST['result']) {
                    unset($_POST['result']);
                    echo '<p class="erreur">Aucune donnée reçue. <a href="'.$versCettePage.'">Retour</a></p>';
                } else {
                    echo '<p class="info">Inscription effectuée. <a href="'.$versCettePage.'">Retour</a></p>';
                }
            } else {
                // On récupère des informations complémentaires sur la sortie : besoin de bénévoles ? possibilité de restaurant ?
                $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
                $req = 'SELECT * FROM `'.$pbd.'evt` WHERE `id_evt` = '.$id_evt;
                $result = $mysqli->query($req);
                while ($sorties = $result->fetch_assoc()) {
                    $sortie = $sorties;
                } ?>
				<p>
					Choisissez le rôle et les éventuelles options individuelles de chacun, puis validez pour confirmer. Attention : chaque utilisateur recevra un e-mail pour
					être averti de son inscription.<br />
					<a href="<?php echo $versCettePage; ?>" title="">&lt; Annuler / retour</a>
				</p>
				<br />

				<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
					<input type="hidden" name="operation" value="user_join_manuel" />
                    <?php if ($id_dest) { ?>
					<input type="hidden" name="id_destination" value="<?php echo $id_dest; ?>" />
                    <?php } ?>

					<?php
                    // MESSAGES A LA SOUMISSION
                    if ('user_join_manuel' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                    }
                // redirection en cas de réussite
                if ('user_join_manuel' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
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
							<?php if ('1' == $sortie['cb_evt']) { ?><th style="font-size:12px; text-align:left">Paiement en ligne</th><?php } ?>
                            <?php if ('1' == $sortie['repas_restaurant']) { ?><th style="font-size:12px; text-align:left">Restaurant</th><?php } ?>
                            <?php if ($id_dest) { ?><th style="font-size:12px; text-align:left">Transport</th><?php } ?>
						</thead>
						<tbody>
							<?php
                            // pour chaque user sélectionné : choix du role, puis confirmation
                            foreach ($_POST['id_user'] as $i => $utilisateur) {
                                echo '<tr>'
                                    .'<td>'
                                        // vars to re-post
                                        .'<input type="hidden" name="id_user[]" value="'.(int) ($_POST['id_user'][$i]).'" />'
                                        .'<input type="hidden" name="civ_user[]" value="'.html_utf8(stripslashes($_POST['civ_user'][$i])).'" />'
                                        .'<input type="hidden" name="lastname_user[]" value="'.html_utf8(stripslashes($_POST['lastname_user'][$i])).'" />'
                                        .'<input type="hidden" name="firstname_user[]" value="'.html_utf8(stripslashes($_POST['firstname_user'][$i])).'" />'
                                        .'<input type="hidden" name="nickname_user[]" value="'.html_utf8(stripslashes($_POST['nickname_user'][$i])).'" />'
                                        // afficher
                                        .html_utf8(stripslashes($_POST['civ_user'][$i])).' '
                                        .html_utf8(stripslashes($_POST['firstname_user'][$i])).' '
                                        .html_utf8(stripslashes($_POST['lastname_user'][$i])).' '
                                    .'</td>'
                                    .'<td>'
                                        .html_utf8(stripslashes($_POST['nickname_user'][$i]))
                                    .'</td>'
                                    .'<td>'
                                        .'<select name="role_evt_join[]">'
                                            .'<option value="manuel">Inscrit (par défaut)</option>'
                                            .(1 == $sortie['need_benevoles_evt'] ? '<option value="benevole">Bénévole</option>' : '')
                                            // .'<option value="coencadrant">Co-encadrant</option>'
                                            // .'<option value="encadrant">Encadrant</option>'
                                        .'</select>'
                                    .'</td>'
                                    .('1' == $sortie['cb_evt'] ? '<td>'
                                        .'<select name="is_cb[]">'
                                            .'<option value="-1" '.('-1' == $_POST['is_cb'][$i] ? ' selected="selected" ' : '').'>NSP</option>'
                                            .'<option value="0" '.('0' == $_POST['is_cb'][$i] ? ' selected="selected" ' : '').'>Non</option>'
                                            .'<option value="1" '.('1' == $_POST['is_cb'][$i] ? ' selected="selected" ' : '').'>Oui</option>'
                                        .'</select>'
                                    .'</td>' : '')
                                    .('1' == $sortie['repas_restaurant'] ? '<td>'
                                        .'<select name="is_restaurant[]">'
                                            .'<option value="-1" '.('-1' == $_POST['is_restaurant'][$i] ? ' selected="selected" ' : '').'>NSP</option>'
                                            .'<option value="0" '.('0' == $_POST['is_restaurant'][$i] ? ' selected="selected" ' : '').'>Non</option>'
                                            .'<option value="1" '.('1' == $_POST['is_restaurant'][$i] ? ' selected="selected" ' : '').'>Oui</option>'
                                        .'</select>'
                                    .'</td>' : '');
                                if ($id_dest) {
                                    echo '<td>'
                                        .'<select name="id_bus_lieu_destination[]">'
                                        .'<option value="-1" '.('-1' == $_POST['id_bus_lieu_destination'][$i] ? ' selected="selected" ' : '').'>Covoiturage</option>';
                                    // toutes les autres options / arrets de bus
                                    $b = 1;
                                    foreach ($busses as $bus) {
                                        if ($bus['ramassage']) {
                                            foreach ($bus['ramassage'] as $point) {
                                                if ($bus['places_disponibles'] > 0) {
                                                    ?>
                                        <option value="<?php echo $point['bdl_id']; ?>" <?php if ($_POST['id_bus_lieu_destination'][$i] == $point['bdl_id']) {
                                                        echo ' selected="selected" ';
                                                    } ?>>
                                            <?php echo $bus['intitule']; ?> - <?php echo $point['nom']; ?>, à <?php echo display_time($point['date']); ?> (<?php echo $bus['places_disponibles']; ?> places restantes)
                                        </option>
                                        <?php
                                                }
                                            }
                                        }
                                    }
                                    echo '</select>'
                                        .'</td>';
                                }
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
?>
