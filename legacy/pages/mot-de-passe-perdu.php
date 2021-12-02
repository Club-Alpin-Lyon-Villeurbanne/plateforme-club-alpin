	<?php
    // première étape (destiné à être ouverte en lightbox !!)
    if (!$p2) {
        ?>

		<form action="<?php echo $versCettePage; ?>" method="post" style="text-align:left;">
			<input type="hidden" name="operation" value="user_mdp_reinit" />

			<?php

            inclure('mdp-perdu', 'fancycontent');

        // TABLEAU
        if ('user_mdp_reinit' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
        if ('user_mdp_reinit' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
            echo '<p class="info">OK, votre e-mail vient d\'être envoyé à '.date('H:i:s', time()).', sur '.html_utf8($email_user).'. <br />Courez vérifier votre boite e-mail.</p>';
        }

        // en cas de succès, pas de suite au form
        if ('user_mdp_reinit' != $_POST['operation'] || (isset($errTab) && count($errTab) > 0)) {
            ?>

				<input type="text" name="email_user" class="type1" value="<?php echo inputVal('email_user', ''); ?>" placeholder="Votre adresse e-mail" />
				<input type="password" name="mdp_user" class="type1"  placeholder="Le nouveau mot de passe" />
				<input type="submit" value="Envoyer" class="nice2" />
				<?php
        } ?>
		</form>
		<script type="text/javascript">
		// REMOVE UGLY AUTOFILLS
		if ($.browser.webkit){	$('input').attr('autocomplete', 'off');	}
		</script>
		<?php
    }

    // seconde étape: traitement dans operations.php, ici juste les erreurs
    else {
        echo '<div class="contenutype1" style="position:relative;z-index:5;margin:30px auto;"><h2 style="color:gray">Reinitialisation du mot de passe...</h2>';
        if (isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur"><b>ERREURS : </b>'.implode(', ', $errTab).'</div>';
        } else {
            echo '<h1>Succès</h1><p>Vous pouvez vous connecter avec votre nouveau mot de passe <a href="profil.html" title="">dans votre espace perso</a>.</p>';
        }
        echo '</div>';
    }
