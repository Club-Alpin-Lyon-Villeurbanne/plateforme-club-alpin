<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;

?>
<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            if (!allowed('comm_edit')) {
                echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour afficher cette page</p>';
            } else {
                // vérification de l'ID de commission
                $id_commission = (int) $_GET['id_commission'];
                $commissionTmp = false;
                $req = "SELECT * FROM caf_commission WHERE id_commission = $id_commission";
                $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
                    $commissionTmp = $handle;
                }

                if (!$commissionTmp) {
                    echo '<p class="erreur"> ID invalide</p>';
                } else {
                    if (!allowed('comm_edit', 'commission:' . $commissionTmp['code_commission'])) {
                        echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour afficher cette page</p>';
                    } else {
                        ?>
					<h1>Modifier une commission</h1>
					<?php inclure($p1, 'vide'); ?>

					<form action="<?php echo $versCettePage . '?id_commission=' . $id_commission; ?>" method="post" enctype="multipart/form-data" class="loading">
						<input type="hidden" name="operation" value="commission_edit" />

						<?php
                        // MESSAGES A LA SOUMISSION
                        if (isset($_POST['operation']) && 'commission_edit' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                            echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                        }
                        if (isset($_POST['operation']) && 'commission_edit' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                            echo '<p class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '.</p>';
                        } ?>

						<hr />
						<div style="float:left; background:white; padding:7px;">
							<a href="<?php echo comFd((int) $commissionTmp['id_commission']); ?>" class="fancybox" title="Image actuelle">
								<img src="<?php echo comFd((int) $commissionTmp['id_commission']) . '?ac=' . time(); ?>" alt="" title="Image actuelle" style="width:150px" />
							</a>
						</div>
						<div style="float:right; width:440px">
							<h2>La grande image de fond</h2>
							<div id="select-bigfond" style="padding:0 0 10px 0;">
								<?php inclure('commission-add-bigimg', 'vide'); ?>
								<input type="file" name="bigfond" /><br />
							</div>
						</div>

						<hr style="clear:both; " />
						<h2>Les trois pictos</h2>
						<div id="select-pictos" style="padding:0 0 10px 0;">
							<?php inclure('commission-add-pictos', 'vide'); ?>
							<br />
							<table style="line-height:20px;">
								<tr>
									<td rowspan="2">
										<div style="float:left; background:white; padding:5px; margin-right:10px">
											<img src="<?php echo comPicto((int) $commissionTmp['id_commission']) . '?ac=' . time(); ?>" alt="" title="Image actuelle" />
										</div>
									</td>
									<td> Pictogramme bleu CAF : <strong>#50b5e1</strong></td>
								</tr>
								<tr>
									<td><input type="file" name="picto" /></td>
								</tr>
								<tr><td>&nbsp;</td></tr>

								<tr>
									<td rowspan="2">
										<div style="float:left; background:#eaeaea; padding:5px; margin-right:10px">
											<img src="<?php echo comPicto((int) $commissionTmp['id_commission'], 'light') . '?ac=' . time(); ?>" alt="" title="Image actuelle" />
										</div>
									</td>
									<td> Pictogramme blanc : <strong>#ffffff</strong></td>
								</tr>
								<tr>
									<td><input type="file" name="picto-light" /></td>
								</tr>
								<tr><td>&nbsp;</td></tr>

								<tr>
									<td rowspan="2">
										<div style="float:left; background:white; padding:5px; margin-right:10px">
											<img src="<?php echo comPicto((int) $commissionTmp['id_commission'], 'dark') . '?ac=' . time(); ?>" alt="" title="Image actuelle" />
										</div>
									</td>
									<td> Pictogramme sombre : <strong>#044e68</strong></td>
								</tr>
								<tr>
									<td><input type="file" name="picto-dark" /></td>
								</tr>

							</table>
						</div>
						<!--
						<p>
							<input type="checkbox" name="disable-pictos" id="disable-pictos" <?php if (isset($_POST['disable-pictos']) && 'on' == $_POST['disable-pictos']) {
							    echo 'checked="checked"';
							} ?>/>
							<label for="disable-pictos" class='mini'>Laisser tomber, utiliser les pictos du CAF par défaut (déconseillé)</label>
						</p>
						-->
						<hr />

						<h2>Nom de la commission :</h2>
						<?php inclure('commission-add-nom', 'vide'); ?>
						<input type="text" name="title_commission" class="type1" value="<?php echo HtmlHelper::escape($commissionTmp['title_commission']); ?>" placeholder="< 25 caractères" />


                        <hr>
                        <h2>Groupes de niveaux :</h2>
                        <div id="groupes">
                            <pre><?php $groupes = get_groupes($id_commission); ?></pre>
                            <ul>
                            <?php foreach ($groupes as $groupe) { ?>
                                <?php if (allowed('comm_groupe_edit')) { ?>
                                    <li style="list-style-type:none;" >
                                        <div class="niveau editable <?php if (0 == $groupe['actif']) {
                                            echo ' vis-off ';
                                        } ?>">
                                            <input type="hidden" name="groupe[<?php echo $groupe['id']; ?>][id]" value="<?php echo $groupe['id']; ?>">
                                            <p><label><b>* Nom :</b></label><br>
                                            <input type="text" name="groupe[<?php echo $groupe['id']; ?>][nom]" value="<?php echo $groupe['nom']; ?>"  class="type1"></p>
                                            <p><label style="float:left">Niveau technique :</label>
                                            <input type="text" name="groupe[<?php echo $groupe['id']; ?>][niveau_technique]" value="<?php echo $groupe['niveau_technique']; ?>"  class="star_rating"/></p>
                                            <p><label style="float:left">Niveau physique :</label>
                                            <input type="text" name="groupe[<?php echo $groupe['id']; ?>][niveau_physique]" value="<?php echo $groupe['niveau_physique']; ?>"  class="star_rating" /></p>
                                            <label>Description :</label>
                                            <textarea name="groupe[<?php echo $groupe['id']; ?>][description]"class="type1"><?php echo $groupe['description']; ?></textarea><br>
                                            <?php if (allowed('comm_groupe_activer_desactiver')) { ?>
                                                <div style="position:absolute;left:5px;bottom:5px;">
                                                <label><input type="radio" name="groupe[<?php echo $groupe['id']; ?>][actif]" value="1" <?php echo 1 == $groupe['actif'] ? 'checked="checked"' : ''; ?>>&nbsp;Actif &nbsp;</label>/<label>&nbsp;
                                                Inactif&nbsp;<input type="radio" name="groupe[<?php echo $groupe['id']; ?>][actif]" value="0" <?php echo 0 == $groupe['actif'] ? 'checked="checked"' : ''; ?>></label>
                                                </div>
                                            <?php } ?>
                                            <?php if (allowed('comm_groupe_delete')) { ?>
                                                <div style="position:absolute;right:5px;top:5px;"><label style="width:auto">Supprimer <input type="checkbox" name="groupe[<?php echo $groupe['id']; ?>][delete]"></label></div><br>
                                            <?php } ?>
                                        </div>
                                    </li>
                                <?php } else { ?>
                                    <li style="list-style-type:none;" >
                                        <div class="niveau <?php if (allowed('comm_groupe_activer_desactiver') || allowed('comm_groupe_delete')) { ?> editable <?php } ?><?php if (0 == $groupe['actif']) {
                                            echo ' vis-off ';
                                        } ?>">
                                            <input type="hidden" name="groupe[<?php echo $groupe['id']; ?>][id]" value="<?php echo $groupe['id']; ?>">
                                            <b><?php echo $groupe['nom']; ?></b>
                                            <p>Niveau physique : <span class="starify"><?php echo $groupe['niveau_physique']; ?></span>, Niveau technique : <span class="starify"><?php echo $groupe['niveau_technique']; ?></span></p>
                                            <?php if ($groupe['description']) { ?><p>Description :<br> <?php echo $groupe['description']; ?></p><?php } ?>
                                            <br>
                                            <?php if (allowed('comm_groupe_activer_desactiver')) { ?>
                                                <div style="position:absolute;left:5px;bottom:5px;">
                                                <label><input type="radio" name="groupe[<?php echo $groupe['id']; ?>][actif]" value="1" <?php echo 1 == $groupe['actif'] ? 'checked="checked"' : ''; ?>>&nbsp;Actif &nbsp;</label>/<label>&nbsp;
                                                Inactif&nbsp;<input type="radio" name="groupe[<?php echo $groupe['id']; ?>][actif]" value="0" <?php echo 0 == $groupe['actif'] ? 'checked="checked"' : ''; ?>></label>
                                                </div>
                                            <?php } ?>
                                            <?php if (allowed('comm_groupe_delete')) { ?>
                                                <div style="position:absolute;right:5px;top:5px;"><label style="width:auto">Supprimer <input type="checkbox" name="groupe[<?php echo $groupe['id']; ?>][delete]"></label></div><br>
                                            <?php } ?>
                                        </div>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                            </ul>
                        </div>

                        <?php if (allowed('comm_groupe_edit')) { ?>
                        <a href="javascript:void(0)" class="add" id="add_group" title="Ajouter un groupe">Ajouter un groupe</a>
                        <div id="add_groups"></div>
                        <?php } ?>

						<hr />
						<br />
						<br />
						<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()" class="biglink">
							<span class="bleucaf">&gt;</span>
							ENREGISTRER LES MODIFICATIONS
						</a>


					</form>
					<br />
					<br />
					<br />
					<br />
					<?php
                    }
                }
            }
?>
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>

	<br style="clear:both" />
</div>

<!-- un peu d'ergoomie... -->
<script type="text/javascript">

	$().ready(function() {

        function genere_add_group() {
            var group_count = $('#add_groups .niveau').length;
            return ''+
                '<div class="niveau">'+
                    '<input type="hidden" name="new_groupe['+group_count+'][id_commission]" value="<?php echo $id_commission; ?>" class="type1" />'+
                    '<p><b>Nouveau groupe</b></p>'+
                    '<p><label>* Nom :</label>'+
                    '<input type="text" name="new_groupe['+group_count+'][nom]" value=""  class="type1"></p>'+
                    '<p><label style="float:left">Niveau technique :</label>'+
                    '<input type="text" name="new_groupe['+group_count+'][niveau_technique]" value=""  class="star_rating"/></p>'+
                    '<p><label style="float:left">Niveau physique :</label>'+
                    '<input type="text" name="new_groupe['+group_count+'][niveau_physique]" value=""  class="star_rating" /></p>'+
                    '<label>Description :</label>'+
                    '<textarea name="new_groupe['+group_count+'][description]" class="type1"></textarea>'+
                '</div>';
        }

		$('#disable-pictos, #disable-bigfond').each(function(){
			var checked = $(this).is(':checked');
			if(checked && $(this).attr('id')=='disable-pictos')		$('#select-pictos').hide();
			if(checked && $(this).attr('id')=='disable-bigfond') 	$('#select-bigfond').hide();
		});
		$('#disable-pictos, #disable-bigfond').bind('click change', function(){
			var checked = $(this).is(':checked');
			if(checked && $(this).attr('id')=='disable-pictos')		$('#select-pictos').slideUp();
			if(!checked && $(this).attr('id')=='disable-pictos')	$('#select-pictos').slideDown();

			if(checked && $(this).attr('id')=='disable-bigfond') 	$('#select-bigfond').slideUp();
			if(!checked && $(this).attr('id')=='disable-bigfond') 	$('#select-bigfond').slideDown();
		});
        $('#add_group').click(function(){
            var contenu = genere_add_group();
            $('#add_groups').append(contenu);
        });
	});
</script>
