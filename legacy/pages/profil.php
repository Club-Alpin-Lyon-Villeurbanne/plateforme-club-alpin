<?php
// page dédiée HORS CONNEXION
// 				à la création d'un nouveau profil
// 				au login
// page dédiée CONNECTÉ
// 				à la gestion de son profil/photo
// 				à l'historique des sorties
// 				aux filiations
// 				...

?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">

		<?php
        // **************** **************************************
        // **************** Non connecté
        // **************** **************************************
        if (!user()) {
            ?>
			<div style="padding:20px;">
				<h1>Activer votre compte</h1>
				<?php
                // ************************
                // FORMULAIRE D'INSCRIPTION

                // texte explicatif
                inclure('activer-profil', 'vide');

            // error
            if (isset($_POST['operation']) && 'user_subscribe' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
            }
            // success
            if (isset($_POST['operation']) && 'user_subscribe' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                echo "
					<h3>Compte créé avec succès</h3>
					<p class='info'>
						Votre compte a été créé, <b>mais vous devez le valider</b> en cliquant sur le lien
						contenu dans l'e-mail que nous venons d'envoyer à ".html_utf8(stripslashes($email_user)).'
					</p>';
            }

            // affichage
            if ('user_subscribe' != ($_POST['operation'] ?? null) || ('user_subscribe' == $_POST['operation'] && isset($errTab) && count($errTab) > 0)) {
                ?>
					<br />
					<form action="<?php echo $versCettePage; ?>" method="post">
						<input type="hidden" name="operation" value="user_subscribe" />

						<div style="float:left; width:45%; padding:5px 20px 5px 0;">
							<b>Votre nom de famille</b><br />
							<p class="mini">Le même que donné lors de votre inscription</p>
							<input type="text" name="lastname_user" class="type1" value="<?php echo inputVal('lastname_user', ''); ?>" placeholder="" /><br />
						</div>

						<div style="float:left; width:45%; padding:5px 20px 5px 0;">
							<b>Votre numéro d'adhérent au CAF</b>
							<p class="mini">Inscrit sur votre carte CAF, sans espace</p>
							<input type="text" name="cafnum_user" class="type1" value="<?php echo inputVal('cafnum_user', ''); ?>" placeholder="" maxlength="<?php echo $limite_longeur_numero_adherent; ?>" /><br />
						</div>

						<div style="float:left; width:45%; padding:5px 20px 5px 0;">
							<b>Votre e-mail</b>
							<p class="mini">Utilisé comme identifiant pour vous connecter</p>
							<input type="text" name="email_user" class="type1" value="<?php echo inputVal('email_user', ''); ?>" placeholder="" /><br />
						</div>

						<div style="float:left; width:45%; padding:5px 20px 5px 0;">
							<b>Choisissez un mot de passe</b>
							<p class="mini">8 à 40 caractères sans espace</p>
							<input type="password" name="mdp_user" class="type1" value="<?php echo inputVal('mdp_user', ''); ?>" placeholder="" /><br />
						</div>

						<br style="clear:both" />
						<br />
						<div style="padding:10px 0">
							<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
								<span class="bleucaf">&gt;</span>
								ACTIVER MON COMPTE
							</a>
						</div>
					</form>
					<?php
            } ?>
			</div>
			<?php
        }

        // **************** **************************************
        // **************** CONNECTÉ
        // **************** **************************************
        else {
            if (file_exists(__DIR__.'/profil-'.$p2.'.php')) {
                include __DIR__.'/profil-'.$p2.'.php';
            } else {
                echo '<p class="erreur">Erreur : fichier introuvable</p>';
            }
        }
        ?>
		<br style="clear:both" />
	</div>

	<!-- partie droite -->

	<div id="right1">
		<div class="right-light">
			&nbsp; <!-- important -->
			<?php
            // PRESENTATION DE LA COMMISSINO
            if (user()) {
                inclure('presentation-'.($current_commission ?: 'general'), 'right-light-in');
            }
            // hors connexion : login
            else {
                ?>
				<br />
				<br />
                <div style="padding:0 10px 0 20px;">
                    <h1>Vous avez déja un compte ?</h1>

                    <?php echo twigRender('login_form.html.twig'); ?>
                </div>
				<?php
            }

            // RECHERCHE
            include __DIR__.'/../includes/recherche.php';
            ?>
		</div>


		<div class="right-green">
			<div class="right-green-in">

				<?php
                // AGENDA sur fond vert
                include __DIR__.'/../includes/droite-agenda.php';
                ?>

			</div>
		</div>

	</div>

	<br style="clear:both" />
</div>
