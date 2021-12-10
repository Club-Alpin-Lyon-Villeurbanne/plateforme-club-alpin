<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            if (!allowed('comm_create')) {
                echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour afficher cette page</p>';
            } else {
                ?>
				<h1>Nouvelle commission</h1>
				<?php inclure($p1, 'vide'); ?>

				<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
					<input type="hidden" name="operation" value="commission_add" />

					<?php
                    // MESSAGES A LA SOUMISSION
                    if (isset($_POST['operation']) && 'commission_add' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                    }
                if (isset($_POST['operation']) && 'commission_add' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                    echo '<p class="info">Mise à jour effectuée à '.date('H:i:s', time()).'.</p>';
                } ?>

					<hr />
					<h2>La grande image de fond</h2>
					<div id="select-bigfond" style="padding:0 0 10px 0;">
						<?php inclure($p1.'-bigimg', 'vide'); ?>
						<input type="file" name="bigfond" /><br />
					</div>
					<p>
						<input type="checkbox" name="disable-bigfond" id="disable-bigfond" <?php if (isset($_POST['disable-bigfond']) && 'on' == $_POST['disable-bigfond']) {
                    echo 'checked="checked"';
                } ?>/>
						<label for="disable-bigfond" class='mini'>Laisser tomber, utiliser l'image par défaut (déconseillé)</label>
					</p>


					<hr />
					<h2>Les trois pictos</h2>
					<div id="select-pictos" style="padding:0 0 10px 0;">
						<?php inclure($p1.'-pictos', 'vide'); ?>
						<br />
						<table style="line-height:20px;">
							<tr>
								<td rowspan="2"><img style="vertical-align: middle;" src="/ftp/commission/0/picto.png" alt="" width="35" height="35" /></td>
								<td> Pictogramme bleu CAF : <strong>#50b5e1</strong></td>
							</tr>
							<tr>
								<td><input type="file" name="picto" /></td>
							</tr>
							<tr><td>&nbsp;</td></tr>

							<tr>
								<td rowspan="2"><img style="vertical-align: middle;" src="/ftp/commission/0/picto-light.png" alt="" width="35" height="35" /></td>
								<td> Pictogramme blanc : <strong>#ffffff</strong></td>
							</tr>
							<tr>
								<td><input type="file" name="picto-light" /></td>
							</tr>
							<tr><td>&nbsp;</td></tr>

							<tr>
								<td rowspan="2"><img style="vertical-align: middle;" src="/ftp/commission/0/picto-dark.png" alt="" width="35" height="35" /></td>
								<td> Pictogramme sombre : <strong>#044e68</strong></td>
							</tr>
							<tr>
								<td><input type="file" name="picto-dark" /></td>
							</tr>

						</table>
					</div>
					<p>
						<input type="checkbox" name="disable-pictos" id="disable-pictos" <?php if ('on' == $_POST['disable-pictos']) {
                    echo 'checked="checked"';
                } ?>/>
						<label for="disable-pictos" class='mini'>Laisser tomber, utiliser les pictos du CAF par défaut (déconseillé)</label>
					</p>
					<hr />

					<h2>Nom de la commission :</h2>
					<?php inclure($p1.'-nom', 'vide'); ?>
					<input type="text" name="title_commission" class="type1" value="<?php echo inputVal('title_commission', ''); ?>" placeholder="< 25 caractères" />

                    <?php if (allowed('comm_groupe_edit')) { ?>
                    <hr>
                    <h2>Groupes de niveaux :</h2>
                    <p class="mini">Vous pourrez gérer les groupes de niveaux au sein de cette commission une fois l'enregistrement effectué.</p>
					<?php } ?>

					<hr />
					<br />
					<br />
					<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()" class="biglink">
						<span class="bleucaf">&gt;</span>
						ENREGISTRER CETTE COMMISSION
					</a>
				</form>
				<br />
				<br />
				<br />
				<br />
				<?php
            }
            ?>
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__.'/../includes/right-type-agenda.php';
    ?>

	<br style="clear:both" />
</div>

<!-- un peu d'ergoomie... -->
<script type="text/javascript">
	$().ready(function() {
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
	});
</script>
