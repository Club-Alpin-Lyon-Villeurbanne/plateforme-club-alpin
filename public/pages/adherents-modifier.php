<?php
if (!admin() && !allowed('user_edit_notme')) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) ($_GET['id_user']);
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit();
    }

    if (null === $userTab || 0 === count($userTab)) {
        include SCRIPTS.'connect_mysqli.php';
        $req = 'SELECT * FROM  `'.$pbd."user` WHERE id_user='".$mysqli->real_escape_string($id_user)."' LIMIT 1";
        $userTab = [];
        $result = $mysqli->query($req);
        $userTab = $result->fetch_assoc();
        $mysqli->close;

        foreach ($userTab as $key => $val) {
            $userTab[$key] = inputVal($key, $userTab[$key]);
        }
    } ?>

	<h1>Modifier un adhérent ou un salarié</h1>

	<p>
		Depuis cette page, vous pouvez modifier une entrée dans la base de données des membres du site.
		Notez bien le mot de passe que vous choisissez, et prenez bien soin d'entrer une adresse e-mail valide !
	</p>

	<hr />

	<form action="<?php echo $versCettePage; ?>" method="post">
		<input type="hidden" name="operation" value="user_edit" />
		<input type="hidden" name="id_user" value="<?php echo $id_user; ?>" />
		<input type="hidden" name="lastname_user" value="<?php echo $userTab['lastname_user']; ?>" />

		<?php
        // TABLEAU
        if ('user_edit' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
    if ('user_edit' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info"><b>Adhérent modifié avec succès :</b> <ul><li>'.implode('</li><li>', $okTab).'</li></ul></div>';
    } else {
        ?>
<!--

			Civilité* :<br />
			<select name="civ_user">
				<option value="M" <?php if ('M' == $_POST['civ_user']) {
            echo 'selected="selected"';
        } ?>>M.</option>
				<option value="MME" <?php if ('MME' == $_POST['civ_user']) {
            echo 'selected="selected"';
        } ?>>Mme.</option>
				<option value="MLLE" <?php if ('MLLE' == $_POST['civ_user']) {
            echo 'selected="selected"';
        } ?>>Mlle.</option>
			</select>
			<br />

			Prénom* :<br />
			<input type="text" name="firstname_user" class="type1" value="<?php echo $userTab['firstname_user']; ?>" placeholder="" />
			<br />

			Nom* :<br />
			<input type="text" name="lastname_user" class="type1" value="<?php echo $userTab['lastname_user']; ?>" placeholder="" />
			<br />

			Pseudonyme* :<br />
			<input type="text" name="nickname_user" class="type1" value="<?php echo $userTab['nickname_user']; ?>" placeholder="" />
			<br />
 -->
			<br />

			<table>
				<tr>
					<td width='100px'><img src="<?php echo userImg($userTab['id_user'], 'pic'); ?>" alt="" title="" style="max-width:100%" /></td>
					<td><h1>
						<?php
                            echo $userTab['civ_user'].' '.$userTab['firstname_user'].' '.$userTab['lastname_user'].'<br />(<a href="/user-full/'.$userTab['id_user'].'.html" title="Fiche profil" target="_top">'.$userTab['nickname_user'].'</a>)'; ?>
						</h1>
					</td>
				</tr>
			</table><br />

			Date d'adhésion ou de renouvellement : <b>
			<?php
                // notification d'alerte si l'user doit renouveler sa licence

                if ($userTab['alerte_renouveler_user']) {
                    echo '<span class="alerte">';
                }
        if ($userTab['date_adhesion_user'] > 0) {
            echo date('d/m/Y', $userTab['date_adhesion_user']);
        } else {
            echo 'auncune date connue.';
        }
        if ($userTab['alerte_renouveler_user']) {
            echo '</span>';
        } ?>
			</b><br />

			<?php
                if (1 != $userTab['valid_user']) {
                    // compte non active pour le moment
                    echo '<br />URL d\'activation du compte : '.$p_racine.'user-confirm/'.$userTab['cookietoken_user'].'-'.$userTab['id_user'].'.html<br />';
                } ?>

			<br />
			E-mail* :<br />
			<input type="text" name="email_user" class="type1" value="<?php echo $userTab['email_user']; ?>" placeholder="" />
			<br />

			Mot de passe :<br />
			<input type="text" name="mdp_user" class="type1" value="" placeholder="" /> (pour ne pas le modifier, laisser vide)
			<br />
<!--
			Confirmer le mot de passe* :<br />
			<input type="password" name="mdp_user_confirm" class="type1" value="" placeholder="" />
			<br />
 -->
			Numéro de licence :<br />
			<input type="text" name="cafnum_user" class="type1" value="<?php echo $userTab['cafnum_user']; ?>" placeholder="" /> à inverser avec le nouveau numéro
			<input type="text" name="cafnum_user_new" class="type1" value="<?php echo $userTab['cafnum_user_new']; ?>" placeholder="" />
			<br />

			<!--
			Date de naissance :<br />
			<input type="text" name="birthday_user" class="type1" value="<?php echo $userTab['birthday_user']; ?>" placeholder="jj/mm/aaaa" />
			<br />

			Numéro de téléphone personnel :<br />
			<input type="text" name="tel_user" class="type1" value="<?php echo $userTab['tel_user']; ?>" placeholder="" />

			<br />
			Numéro de téléphone de sécurité :<br />
			<input type="text" name="tel2_user" class="type1" value="<?php echo $userTab['tel2_user']; ?>" placeholder="" />

			<br />
			Adresse <br />
			<input type="text" name="adresse_user" class="type1" value="<?php echo $userTab['adresse_user']; ?>" placeholder="Numéro, rue..." /><br />
			<input type="text" name="cp_user" style="width:70px" class="type1" value="<?php echo $userTab['cp_user']; ?>" placeholder="Code postal" />
			<input type="text" name="ville_user" class="type1" value="<?php echo $userTab['ville_user']; ?>" placeholder="Ville" /><br />
			<input type="text" name="pays_user" class="type1" value="<?php echo $userTab['pays_user']; ?>" placeholder="Pays" /><br />



			-->


			<br />
			Qui peut le / la contacter sur le site, via un formulaire de contact (adresse e-mail jamais dévoilée) ?<br />
			<?php $whocan_selected = $userTab['auth_contact_user']; ?>
            <?php $whocan_table = false; ?>
            <?php include INCLUDES.'user'.DS.'whocan_contact.php'; ?>

			<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
				<img src="img/base/save.png" alt="Enregistrer" title="Enregistrer" style="height:35px;" />
				Enregistrer
			</a>
			<?php
    } ?>
	</form>

<?php
}
?>
