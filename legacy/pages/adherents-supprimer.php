<?php

use App\Helper\HtmlHelper;
use App\Security\SecurityConstants;

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) $_GET['id_user'];
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit;
    } ?>

	<div style="text-align:left;">
		<h1>Supprimer le compte de l'utilisateur : <?php echo HtmlHelper::escape(stripslashes($_GET['nom'])); ?></h1><br />

		<p>
			<h2>Voulez-vous vraiment supprimer ce compte utilisateur ?<br />Cette personne ne pourra plus se connecter au site en tant qu'utilisateur.<br />Toutes les informations le concernant seront effacées.<br /></h2>
		</p>

		<form action="<?php echo $versCettePage; ?>" method="post">
			<input type="hidden" name="operation" value="user_delete" />
			<input type="hidden" name="id_user" value="<?php echo $id_user; ?>" />

			<?php
            // TABLEAU
            if (isset($_POST['operation']) && 'user_delete' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
            }
    if (isset($_POST['operation']) && 'user_delete' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Utilisateur supprimé ! (Vous devrez <a href="javascript:top.$.fancybox.close();top.frames.location.reload(false);">Recharger la page</a> pour voir le changement)</p>';
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
