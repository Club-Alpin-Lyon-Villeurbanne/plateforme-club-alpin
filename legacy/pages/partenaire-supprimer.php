<?php

if (!admin()) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $part_id = (int) ($_GET['part_id']);
    if ($part_id <= 0) {
        echo 'Erreur : id invalide';
        exit();
    }

    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    if ($part_id > 0) {
        $req = 'SELECT * FROM  `'.$pbd."partenaires` WHERE part_id='".$mysqli->real_escape_string($part_id)."' LIMIT 1";
        $partenaireTab = [];
        $result = $mysqli->query($req);
        $partenaireTab = $result->fetch_assoc();

        if (count($partenaireTab) > 0) {
            foreach ($partenaireTab as $key => $val) {
                $partenaireTab[$key] = inputVal($key, $partenaireTab[$key]);
            }
        } else {
            echo 'Erreur : id invalide';
            exit;
        }
    } ?>

	<div style="text-align:left;">
		<h1>Supprimer le partenaire : <?php echo html_utf8(stripslashes($partenaireTab['part_name'])); ?></h1><br />

		<p>
		<?php
            if (file_exists(__DIR__.'/../../public/ftp/partenaires/'.$partenaireTab['part_image'])) {
                echo "<img src='/ftp/partenaires/".$partenaireTab['part_image']."' width='250px'>";
            } else {
                echo '<img src="/img/base/cross.png" width="25" height="25" alt="non trouvée" />';
            } ?>
		</p>

		<p>
			<h2>Voulez-vous vraiment supprimer ce partenaire (le fichier du logo sera effacé) ?</h2>
		</p>

		<form action="<?php echo $versCettePage; ?>" method="post" onSubmit="javascript:top.$.fancybox.close()" >
			<input type="hidden" name="operation" value="partenaire_delete" />
			<input type="hidden" name="part_id" value="<?php echo $part_id; ?>" />
			<input type="hidden" name="part_image" value="<?php echo $partenaireTab['part_image']; ?>" />

			<?php
            // TABLEAU
            if ('partenaire_delete' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
            }
    if ('partenaire_delete' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Partenaire supprimé ! (Vous devrez <a href="javascript:top.$.fancybox.close();top.parent.location.reload(false);">Recharger la page</a> pour voir le changement)</p>';
    } else {
        ?>
				<input type="submit" class="nice2 orange" value="Supprimer" />
				<a href="javascript:top.$.fancybox.close();" title="" class="nice2">Annuler</a>
				<?php
    } ?>
		</form>
	</div>

	<?php
}
?>
