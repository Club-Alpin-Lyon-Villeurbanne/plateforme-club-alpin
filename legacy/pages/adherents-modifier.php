<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$idUser = $userTab['id_user'] ?? null;
$civUser = $userTab['civ_user'] ?? null;
$firstnameUser = $userTab['firstname_user'] ??  null;
$lastnameUser = $userTab['lastname_user'] ?? null;
$nicknameUser = $userTab['nickname_user'] ?? null;
$alerteRenouvelerUser = $userTab['alerte_renouveler_user'] ?? null;
$dateAdhesionUser = $userTab['date_adhesion_user'] ?? null;
$birthdayUser = $userTab['birthday_user'] ?? null;
$telUser = $userTab['tel_user'] ?? null;
$telSecuUser = $userTab['tel2_user'] ?? null;
$adresseUser = $userTab['adresse_user'] ?? null;
$cpUser = $userTab['cp_user'] ?? null;
$villeUser = $userTab['ville_user'] ?? null;
$paysUser = $userTab['pays_user'] ?? null;
$validUser = $userTab['valid_user'] ?? null;
$cafnumUserNew = $userTab['cafnum_user_new'] ?? null;
$cafnumUser = $userTab['cafnum_user'] ?? null;
$authContactUser = $userTab['auth_contact_user'] ?? null;
$userEmail = $userTab['email_user'] ?? null;

$operation = $_POST['operation'] ?? null;

if (!admin() && !allowed('user_edit_notme')) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) $_GET['id_user'];
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit;
    }

    if (empty($userTab)) {
        $req = "SELECT * FROM  `caf_user` WHERE id_user='".LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_user)."' LIMIT 1";
        $userTab = [];
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $userTab = $result->fetch_assoc();

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
		<input type="hidden" name="lastname_user" value="<?php echo $lastnameUser; ?>" />

		<?php

        // TABLEAU
        if ('user_edit' == $operation && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
    if ('user_edit' == $operation && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info"><b>Adhérent modifié avec succès :</b> <ul><li>'.implode('</li><li>', $okTab).'</li></ul></div>';
    } else {
        ?>
        <br />

			<table>
				<tr>
					<td width='100px'><img src="<?php echo userImg($idUser, 'pic'); ?>" alt="" title="" style="max-width:100%" /></td>
					<td><h1>
						<?php

                            echo $civUser.' '.$firstnameUser.' '.$lastnameUser.'<br/>
                            (<a href="/user-full/'.$idUser.'.html" title="Fiche profil" target="_top">'.$nicknameUser.'</a>)'; ?>
						</h1>
					</td>
				</tr>
			</table><br />

			Date d'adhésion ou de renouvellement : <b>
			<?php
                // notification d'alerte si l'user doit renouveler sa licence

                if ($alerteRenouvelerUser) {
                    echo '<span class="alerte">';
                }
        if ($dateAdhesionUser > 0) {
            echo date('d/m/Y', $dateAdhesionUser);
        } else {
            echo 'aucune date connue.';
        }
        if ($alerteRenouvelerUser) {
            echo '</span>';
        } ?>
			</b><br />

			<?php
                if (isset($validUser) && 1 != $validUser) {
                    // compte non actif pour le moment (0 inactif, 1 actif)
                    echo '<br />URL d\'activation du compte : '.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'user-confirm/'.$userTab['cookietoken_user'].'-'.$idUser.'.html<br />';
                } ?>

			<br />
			E-mail* :<br />
			<input type="text" name="email_user" class="type1" value="<?php echo $userEmail; ?>" placeholder="" />
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
			<input type="text" name="cafnum_user" class="type1" value="<?php echo $cafnumUser; ?>" placeholder="" /> à inverser avec le nouveau numéro
			<input type="text" name="cafnum_user_new" class="type1" value="<?php if($cafnumUserNew) echo $cafnumUserNew; ?>" placeholder="" />
			<br />

			<!--
			Date de naissance :<br />
			<input type="text" name="birthday_user" class="type1" value="<?php echo $birthdayUser; ?>" placeholder="jj/mm/aaaa" />
			<br />

			Numéro de téléphone personnel :<br />
			<input type="text" name="tel_user" class="type1" value="<?php echo $telUser; ?>" placeholder="" />

			<br />
			Numéro de téléphone de sécurité :<br />
			<input type="text" name="tel2_user" class="type1" value="<?php echo $telSecuUser; ?>" placeholder="" />

			<br />
			Adresse <br />
			<input type="text" name="adresse_user" class="type1" value="<?php echo $adresseUser; ?>" placeholder="Numéro, rue..." /><br />
			<input type="text" name="cp_user" style="width:70px" class="type1" value="<?php echo $cpUser; ?>" placeholder="Code postal" />
			<input type="text" name="ville_user" class="type1" value="<?php echo $villeUser; ?>" placeholder="Ville" /><br />
			<input type="text" name="pays_user" class="type1" value="<?php echo $paysUser; ?>" placeholder="Pays" /><br />



			-->


			<br />
			Qui peut le / la contacter sur le site, via un formulaire de contact (adresse e-mail jamais dévoilée) ?<br />
			<?php $whocan_selected = $authContactUser; ?>
            <?php $whocan_table = false; ?>
            <?php require __DIR__.'/../includes/user/whocan_contact.php'; ?>

			<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
				<img src="/img/base/save.png" alt="Enregistrer" title="Enregistrer" style="height:35px;" />
				Enregistrer
			</a>
			<?php
    } ?>
	</form>

<?php
}
