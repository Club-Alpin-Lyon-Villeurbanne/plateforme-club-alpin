<!-- MAIN -->
<div id="main" role="main">
	<div style="padding:20px 10px;">
		<?php
		if(!allowed('user_create_manually')) echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour accéder à cette page</p>';
		else{
			?>

			<h1>Créer un adhérent ou un salarié</h1>

			<p>
				Depuis cette page, vous pouvez créer une nouvelle entrée dans la base de données des membres du site.
				Ainsi il n'est pas nécessaire que la personne que vous souhaitez inscrire soit réellement adhérent du CAF.
				Notez bien le mot de passe que vous choisissez, et prenez bien soin d'entrer une adresse e-mail valide !
			</p>
			<p>
				Dans la <a href="adherents.html" title="" target="_top">page adhérents</a>, les utilisateurs créés ici sont signalés par le logo <img src="img/base/user_manuel.png" alt="" title="M : créé MANUELLEMENT" />.
				Une fois votre adhérent créé, rendez-vous sur la <a href="adherents.html" title="" target="_top">page adhérents</a> pour lui attribuer les
				types d'adhérents désirés (exemple : <i>salarié</i>) en cliquant sur le bouton <img src="img/base/user_star.png" alt="" title="" />.
			</p>
			<hr />

			<form action="<?php echo $versCettePage;?>" method="post">
				<input type="hidden" name="operation" value="user_create" />

				<?php
				// TABLEAU
				if($_POST['operation'] == 'user_create' && sizeof($errTab))	echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
				if($_POST['operation'] == 'user_create' && !sizeof($errTab))	echo '<p class="info">Nouvel adhérent bien créé à '.date("H:i:s", $p_time).'.<br />
					Rendez-vous sur la <a href="adherents.html" title="" target="_top">page adhérents</a> pour lui attribuer les
					status désirés (exemple : <i>salarié</i>) en cliquant sur le bouton <img src="img/base/user_star.png" alt="" title="" />.</p>';

				else{
				?>

					Civilité* :<br />
					<select name="civ_user">
						<option value="M" <?php if($_POST['civ_user']=='M') echo 'selected="selected"';?>>M.</option>
						<option value="MME" <?php if($_POST['civ_user']=='MME') echo 'selected="selected"';?>>Mme.</option>
						<option value="MLLE" <?php if($_POST['civ_user']=='MLLE') echo 'selected="selected"';?>>Mlle.</option>
					</select>
					<br />

					Prénom* :<br />
					<input type="text" name="firstname_user" class="type1" value="<?php echo inputVal('firstname_user', '');?>" placeholder="" />
					<br />

					Nom* :<br />
					<input type="text" name="lastname_user" class="type1" value="<?php echo inputVal('lastname_user', '');?>" placeholder="" />
					<br />

					Pseudonyme* :<br />
					<input type="text" name="nickname_user" class="type1" value="<?php echo inputVal('nickname_user', '');?>" placeholder="" />
					<br />

					E-mail* :<br />
					<input type="text" name="email_user" class="type1" value="<?php echo inputVal('email_user', '');?>" placeholder="" />
					<br />

					Mot de passe désiré* :<br />
					<input type="password" name="mdp_user" class="type1" value="" placeholder="" />
					<br />

					Confirmer le mot de passe* :<br />
					<input type="password" name="mdp_user_confirm" class="type1" value="" placeholder="" />
					<br />

					Numéro de licence :<br />
					<input type="text" name="cafnum_user" class="type1" value="<?php echo inputVal('cafnum_user', '');?>" placeholder="" />
					<br />

					Date de naissance :<br />
					<input type="text" name="birthday_user" class="type1" value="<?php echo inputVal('birthday_user', '');?>" placeholder="jj/mm/aaaa" />
					<br />


					Numéro de téléphone personnel :<br />
					<input type="text" name="tel_user" class="type1" value="<?php echo inputVal('tel_user'); ?>" placeholder="" />

					<br />
					Numéro de téléphone de sécurité :<br />
					<input type="text" name="tel2_user" class="type1" value="<?php echo inputVal('tel2_user'); ?>" placeholder="" />

					<br />
					Adresse <br />
					<input type="text" name="adresse_user" class="type1" value="<?php echo inputVal('adresse_user'); ?>" placeholder="Numéro, rue..." /><br />
					<input type="text" name="cp_user" style="width:70px" class="type1" value="<?php echo inputVal('cp_user'); ?>" placeholder="Code postal" />
					<input type="text" name="ville_user" class="type1" value="<?php echo inputVal('ville_user'); ?>" placeholder="Ville" /><br />
					<input type="text" name="pays_user" class="type1" value="<?php echo inputVal('pays_user'); ?>" placeholder="Pays" /><br />

					<br />
					Qui peut le / la contacter sur le site, via un formulaire de contact (adresse e-mail jamais dévoilée) ?<br />
                    <?php $whocan_selected = $tmpUser['auth_contact_user']; ?>
                    <?php $whocan_table = true; ?>
                    <?php include (INCLUDES.'user'.DS.'whocan_contact.php'); ?>

					<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
						<img src="img/base/save.png" alt="Enregistrer" title="Enregistrer" style="height:35px;" />
						Enregistrer
					</a>
					<?php
				}
				?>
			</form>

			<?php
		}
		?>
		<br style="clear:both" />
	</div>
</div>