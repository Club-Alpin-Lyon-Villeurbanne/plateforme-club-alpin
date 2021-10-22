<!-- MAIN -->
<div id="main" role="main" style="width:100%">
	<div style="padding:20px 10px;">
		<?php
        if (!allowed('user_see_all')) {
            echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour accéder à cette page</p>';
        } else {
            ?>

			<h1>Gestion des adhérents</h1>
			<p>
				<img src="img/base/magnifier.png" style="vertical-align:middle" />
				Le champ "<i>Search</i>" en haut à droite du tableau vous permet de rechercher n'importe quelle valeur instantanément.<br />
				<img src="img/base/database_go.png" style="vertical-align:middle" />
				Les boutons de droite vous permettent d'exporter le tableau courant, le plus utile étant l'exportation en .csv.<br />
				<img src="img/base/info.png" style="vertical-align:middle" />
				Vous pouvez trier les résultats selon différents critères en même temps, en pressant la touche <i>Maj / Shift</i> en cliquant sur les titres des colonnes.<br />
			</p>

			<p><strong>Voir les adhérents :</strong>
				<a href="adherents.html" <?php if ('valid' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> compte activé / licence valide </a>&nbsp;
				<a href="adherents.html?show=valid-expired" <?php if ('valid-expired' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> compte activé / licence expirée </a>&nbsp;
				<a href="adherents.html?show=notvalid" <?php if ('notvalid' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> compte non activé / licence valide </a>&nbsp;
				<a href="adherents.html?show=expired" <?php if ('expired' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> licence expirée </a>&nbsp;
				<a href="adherents.html?show=dels" <?php if ('dels' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> désactivés manuellement </a>&nbsp;
				<a href="adherents.html?show=manual" <?php if ('manual' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> créés manuellement </a>&nbsp;
				<a href="adherents.html?show=nomade" <?php if ('nomade' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> nomades </a>&nbsp;
				<a href="adherents.html?show=all" <?php if ('all' == $show) {
                echo 'style="background:#d3d6ff"';
            } ?> class="boutonFancy"> tous (+long) </a>
			</p>

			<!-- AFFICHAGE DU TABLEAU -->
			<br />
			<br />
			<link rel="stylesheet" href="tools/datatables/extras/TableTools/media/css/TableTools.css" type="text/css" media="screen" />
			<script type="text/javascript" src="tools/datatables/extras/TableTools/media/js/TableTools.min.js"></script>

			<script type="text/javascript">
			$(document).ready(function() {
				$('#pagesLibres').dataTable( {
					"iDisplayLength": 100,
					"aaSorting": [[ 2, "desc" ], [ 4, "asc" ]],
					"sDom": 'T<"clear">lfrtip',
					"oTableTools": {
						"sSwfPath": "tools/datatables/extras/TableTools/media/swf/copy_csv_xls_pdf.swf",
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
			<table id="pagesLibres" class="datatables ">
				<thead>
					<tr>
						<th>Outils</th>
						<th>n° CAF / Infos / DBID </th>
						<!-- <th>Actif ?</th> -->
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
						<!--<th>Droits</th>-->
					</tr>
				</thead>
				<tbody>
					<?php
                    $total = 0;

            for ($i = 0; $i < count($userTab); ++$i) {
                $elt = $userTab[$i];

                echo '<tr id="tr-'.$elt['id_user'].'" class="'.($elt['valid_user'] ? 'vis-on' : 'vis-off').'">'

                            // OUTILS
                            .'<td style="white-space:nowrap;">';
                // seulement ceux valides
                //								if($elt['valid_user']){

                // gestion des droits
                if (allowed('user_giveright_1') || allowed('user_giveright_2') || allowed('user_givepresidence')) {
                    echo '<a href="includer.php?p=pages/adherents-droits.php&amp;id_user='.(int) ($elt['id_user']).'&amp;nom='.urlencode($elt['civ_user'].' '.$elt['firstname_user'].' '.$elt['lastname_user']).'" class="fancyframe" title="Voir / Attribuer des statuts à cet utilisateur"><img src="img/base/user_star.png" alt="RIGHTS" title=""></a> ';
                }

                // désactiver
                if (allowed('user_desactivate_any') && '1' == $elt['valid_user']) {
                    echo '<a href="includer.php?p=pages/adherents-desactiver.php&amp;id_user='.(int) ($elt['id_user']).'&amp;nom='.urlencode($elt['civ_user'].' '.$elt['firstname_user'].' '.$elt['lastname_user']).'" class="fancyframe" title="Désactiver le compte de cet utilisateur"><img src="img/base/user_unvalidate.png" alt="DESACTIVER" title=""></a> ';
                }
                // réactiver
                if (allowed('user_reactivate') && '2' == $elt['valid_user']) {
                    echo '<a href="includer.php?p=pages/adherents-reactiver.php&amp;id_user='.(int) ($elt['id_user']).'&amp;nom='.urlencode($elt['civ_user'].' '.$elt['firstname_user'].' '.$elt['lastname_user']).'" class="fancyframe" title="Réactiver le compte de cet utilisateur"><img src="img/base/user_revalidate.png" alt="REACTIVER" title=""></a> ';
                }

                // reset user
                if (allowed('user_reset')) {
                    echo '<a href="includer.php?p=pages/adherents-reset.php&amp;id_user='.(int) ($elt['id_user']).'&amp;nom='.urlencode($elt['civ_user'].' '.$elt['firstname_user'].' '.$elt['lastname_user']).'" class="fancyframe" title="Remettre à zéro, réinitialiser le compte de cet utilisateur"><img src="img/base/user_reset.png" alt="RESET" title=""></a> ';
                }

                // edit user
                if (allowed('user_edit_notme')) {
                    echo '<a href="includer.php?p=pages/adherents-modifier.php&amp;id_user='.(int) ($elt['id_user']).'" class="fancyframe" title="Modifier cet adhérent"><img src="img/base/user_edit.png" alt="MODIFIER" title=""></a> ';
                }

                //								}

                echo '</td>';

                $img_lock = '<img src="img/base/lock_gray.png" alt="caché"  title="Vous devez disposer de droits supérieurs pour afficher cette information" />';

                // INFOS
                echo '<td>'
                                .html_utf8($elt['cafnum_user']).'<br />'
                                .($elt['manuel_user'] ? '<img src="img/base/user_manuel.png" alt="MANUEL" title="Utilisateur créé manuellement" /> ' : '')
                                .($elt['nomade_user'] ? '<img src="img/base/nomade_user.png" alt="NOMADE" title="Utilisateur nomade" /> ' : '')
                                .('2' == $elt['valid_user'] ? '<img src="img/base/user_desactive.png" alt="DESACTIVE" title="Utilisateur désactivé manuellement" /> ' : '')
                                .(int) ($elt['id_user']).' '
                            .'</td>'
                            //.'<td>'.intval($elt['valid_user']).'</td>'
                            .'<td>'.html_utf8($elt['civ_user']).'</td>'
                            .'<td>'.html_utf8($elt['lastname_user']).'</td>'
                            .'<td>'.html_utf8($elt['firstname_user']).'</td>'
                            .'<td>'.(allowed('user_read_private') ? ($elt['date_adhesion_user'] ? date('Y-m-d', $elt['date_adhesion_user']) : '-') : $img_lock).'</td>'
                            .'<td>'.userlink($elt['id_user'], $elt['nickname_user']).'</td>'
                            .'<td>'.(allowed('user_read_private') ? '<span style="display:none">'.$elt['birthday_user'].'</span>'.($elt['birthday_user'] ? (int) ($elt['birthday_user']).' ans' : '...') : $img_lock).'</td>'
                            .'<td>'.(allowed('user_read_private') ? html_utf8($elt['tel_user']).'<br />'.html_utf8($elt['tel2_user']) : $img_lock).'</td>'
                            .'<td>'.(allowed('user_read_private') ? '<a href="mailto:'.html_utf8($elt['email_user']).'" title="Contact direct">'.html_utf8($elt['email_user']).'</a>' : $img_lock).'</td>'
                            //.'<td>'.(allowed('user_read_private')?nl2br(html_utf8($elt['adresse_user'])):$img_lock).'</td>'
                            .'<td>'.(allowed('user_read_private') ? html_utf8($elt['cp_user']) : $img_lock).'</td>'
                            .'<td>'.(allowed('user_read_private') ? html_utf8($elt['ville_user']) : $img_lock).'</td>'
                            //.'<td>'.(allowed('user_read_private')?html_utf8($elt['pays_user']):$img_lock).'</td>'
                            // .'<td></td>'

                        .'</tr>';
            } ?>
				</tbody>
			</table>

			<?php
        }
        ?>
		<br style="clear:both" />
	</div>
</div>