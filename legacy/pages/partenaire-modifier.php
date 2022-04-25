<?php

use App\Legacy\LegacyContainer;

if (!admin()) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $operation = 'edit';
    $part_id = (int) ($_GET['part_id']);
    if (!$part_id) {
        echo 'Erreur : id invalide';
        exit();
    }
    if (-1 == $part_id) {
        // nouveau
        $operation = 'add';
        echo '<h1>Ajouter un partenaire</h1>';
    } else {
        echo '<h1>Modifier un partenaire</h1>';
    }

    if (0 == count($partenaireTab)) {
        if ('edit' == $operation) {
            $req = "SELECT * FROM  `caf_partenaires` WHERE part_id='".LegacyContainer::get('legacy_mysqli_handler')->escapeString($part_id)."' LIMIT 1";
            $partenaireTab = [];
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $partenaireTab = $result->fetch_assoc();

            foreach ($partenaireTab as $key => $val) {
                $partenaireTab[$key] = inputVal($key, $partenaireTab[$key]);
            }
        } else {
            $partenaireTab['part_name'] = '';
            $partenaireTab['part_desc'] = '';
            $partenaireTab['part_url'] = 'https://';
            $partenaireTab['part_image'] = null;
            $partenaireTab['part_type'] = 1;
            $partenaireTab['part_order'] = 999;
            $partenaireTab['part_enable'] = 0;
        }
    } ?>

	<p>
		Depuis cette page, vous pouvez modifier une entrée dans la base de données des partenaires du site.
	</p>

	<hr />

	<form enctype="multipart/form-data" action="<?php echo $versCettePage; ?>" method="post">
		<input type="hidden" name="MAX_FILE_SIZE" value="50000" />
		<input type="hidden" name="operation" value="partenaire_<?php echo $operation; ?>" />
		<input type="hidden" name="part_id" value="<?php echo $part_id; ?>" />
		<input type="hidden" name="part_image" value="<?php echo $partenaireTab['part_image']; ?>" />

		<?php
        // TABLEAU
        if (isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
    if (isset($_POST['operation']) && 'partenaire_edit' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info"><b>Partenaire modifié avec succès :</b> <ul><li>'.implode('</li><li>', $okTab).'</li></ul></div>';
    }
    if (isset($_POST['operation']) && 'partenaire_add' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info"><b>Partenaire ajouté avec succès :</b> <ul><li>'.implode('</li><li>', $okTab).'</li></ul></div>';
    } else {
        ?>

			<br style="clear:both" />

			<table width="80%">
				<tr>
					<td>Nom :<br />
						<input type="text" name="part_name" class="type1" value="<?php echo $partenaireTab['part_name']; ?>" placeholder="" />
					</td>
					<td>Ordre affichage :<br />
						<input type="text" name="part_order" class="type1" value="<?php echo $partenaireTab['part_order']; ?>" placeholder="" />
					</td>
				</tr>
				<tr>
					<td colspan=2><br style="clear:both" />Description :<br />
						<textarea name="part_desc" rows="3" cols="50"><?php echo $partenaireTab['part_desc']; ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan=2><br style="clear:both" />URL de redirection du logo :<br />
						<input type="text" name="part_url" class="type1" style="width:600px" value="<?php echo $partenaireTab['part_url']; ?>" placeholder="" />
					</td>
				</tr>
				<tr>
					<td colspan=2><br style="clear:both" />Image PNG (250 x 100, transparente):<br />
						<table>
							<tr>
								<td valign='top'>
									<!-- <input type="text" name="part_image" class="type1" value="<?php echo $partenaireTab['part_image']; ?>" placeholder="" /> -->
									<input name="part_image" type="file">
									<br style="clear:both" /><br style="clear:both" />
								</td>
								<td valign='top'>
									<?php
                                        if ('edit' == $operation) {
                                            if (file_exists(__DIR__.'/../../public/ftp/partenaires/'.$partenaireTab['part_image'])) {
                                                echo "<img src='/ftp/partenaires/".$partenaireTab['part_image']."' style='max-width:150px;max-height:60px'>";
                                            } else {
                                                echo '<img src="/img/base/cross.png" width="25" height="25" alt="non trouvée" />';
                                            }
                                        } ?>

								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan=2>Type de partenaire :<br />
						<div class="nice-checkboxes">
							<label for="part_type_private">
								<input type="radio" <?php if (1 == $partenaireTab['part_type']) {
                                            echo 'checked="checked"';
                                        } ?> name="part_type" value="1" id="part_type_private" />
								Privé
							</label>
							<label for="part_type_public">
								<input type="radio" <?php if (2 == $partenaireTab['part_type']) {
                                            echo 'checked="checked"';
                                        } ?> name="part_type" value="2" id="part_type_public" />
								Public
							</label>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan=2><br style="clear:both" />Statut :<br />
						<div class="nice-checkboxes">
							<label for="part_enable_yes">
								<input type="radio" <?php if (1 == $partenaireTab['part_enable']) {
                                            echo 'checked="checked"';
                                        } ?> name="part_enable" value="1" id="part_enable_yes" />
								Activé
							</label>
							<label for="part_enable_no">
								<input type="radio" <?php if (1 != $partenaireTab['part_enable']) {
                                            echo 'checked="checked"';
                                        } ?> name="part_enable" value="0" id="part_enable_no" />
								Désactivé
							</label>
						</div>
					</td>
				</tr>
			</table>
			<br style="clear:both" />

			<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
				<img src="/img/base/save.png" alt="Enregistrer" title="Enregistrer" style="height:35px;" />
				Enregistrer
			</a>
			<?php
    } ?>
	</form>

<?php
}
?>
