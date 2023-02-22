<?php
if (!allowed('user_desactivate_any')) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) $_GET['id_user'];
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit;
    } ?>

	<div style="text-align:left;">
		<h1>Désactiver le compte de l'utilisateur : <?php echo html_utf8(stripslashes($_GET['nom'])); ?></h1><br />

		<p>
			Voulez-vous vraiment désactiver ce compte utilisateur ? Il sera toujours existant dans la base de donnée,
			et pourra être réactivé à tout moment. Cette personne ne pourra plus se connecter au site en tant qu'utilisateur.<br />
			<img src="/img/base/user_desactive.png" alt="DESACTIVE" title="Utilisateur désactivé manuellement" /> Cette icône apparaîtra
			dans la ligne concernée sur la page des <a href="/adherents.html?show=dels" title="" target="_top">adhérents désactivés</a>.
		</p>

		<form action="<?php echo $versCettePage; ?>" method="post">
			<input type="hidden" name="operation" value="user_desactiver" />
			<input type="hidden" name="id_user" value="<?php echo $id_user; ?>" />

			<?php
            // TABLEAU
            if (isset($_POST['operation']) && 'user_desactiver' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
            }
    if (isset($_POST['operation']) && 'user_desactiver' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Utilisateur désactivé (Vous devrez <a href="/adherents.html" title="" target="_top">actualiser la page</a> pour voir le changement)</p>';
    } else {
        ?>
				<input type="submit" class="nice2 orange" value="Désactiver" />
				<a href="javascript:top.$.fancybox.close()" title="" class="nice2">Annuler</a>
				<?php
    } ?>
		</form>
	</div>


	<?php
}
