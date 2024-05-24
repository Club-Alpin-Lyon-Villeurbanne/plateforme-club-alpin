<?php

use App\Legacy\LegacyContainer;

// Cette page sert à joindre manuellement un user à une sortie

if (user()) {
    // id de la sortie
    $id_evt = (int) $_GET['id_evt'];
    $req = 'SELECT * FROM `caf_evt` WHERE `id_evt` = '.$id_evt;
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($sorties = $result->fetch_assoc()) {
        $sortie = $sorties;
    }

    if (!$id_evt) {
        echo '<p class="erreur">ID de sortie non spécifié</p>';
    } else {
        ?>

		<h1>Inscrire un invité "nomade" à cette sortie</h1>

		<?php inclure('explication-nomades', 'vide'); ?>

		<form action="<?php echo $versCettePage; ?>" method="post" class="loading">
			<input type="hidden" name="operation" value="user_join_nomade" />

			<?php
            // MESSAGES A LA SOUMISSION
            if (isset($_POST['operation']) && 'user_join_nomade' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
            }
        // redirection en cas de réussite
        if (isset($_POST['operation']) && 'user_join_nomade' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
            ?>
				<p class="info">Envoi effectué. Actualisez cette page pour afficher les modifications.</p>
				<script type="text/javascript">
					top.window.location.href=top.window.location.href;
					top.window.location.reload();
				</script>
				<?php
        } ?>
			<hr />

			<div>
				<b>- Reprendre un nomade déja créé :</b><br />
				<p class="mini">
					Ceci a pour effet de remplacer les valeurs ci-dessous par un nomade ajouté antérieurement.<br />
					Vous ne pouvez alors plus modifier les infos saisies.
				</p>
				<script type="text/javascript">
					var prefilled = new Array();
				</script>
				<select name="id_user" class="type1" style="width:40%">
					<option value="0" <?php if (isset($_POST['id_user']) && '0' == $_POST['id_user']) {
					    echo 'selected="selected"';
					} ?>>- Non merci, créer un nouvel adhérent nomade</option>
					<?php
					            // liste des adhérents (table user) créés par moi
					$req = 'SELECT  id_user, cafnum_user, firstname_user, lastname_user, civ_user
								, created_user, tel_user, tel2_user, email_user
						FROM  caf_user
						WHERE valid_user=1
						AND nomade_user=1
						ORDER BY created_user DESC
						LIMIT 1000';
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($row = $result->fetch_assoc()) {
            echo '<option value="'.(int) $row['id_user'].'">'.html_utf8($row['cafnum_user'].' - '.$row['firstname_user'].' '.$row['lastname_user']).' - le '.date('d/m/y', $row['created_user']).'</option>';

            echo '
						<script type="text/javascript">
							prefilled[prefilled.length] = {
								"civ_user": "'.addslashes($row['civ_user']).'",
								"cafnum_user": "'.addslashes($row['cafnum_user']).'",
								"firstname_user": "'.addslashes($row['firstname_user']).'",
								"lastname_user": "'.addslashes($row['lastname_user']).'",
								"tel_user": "'.addslashes($row['tel_user']).'",
								"tel2_user": "'.addslashes($row['tel2_user']).'",
								"email_user": "'.addslashes($row['email_user']).'"
							};
						</script>';
        } ?>
				</select>
			</div>
			<hr />


			<div class="tiers clear">
				<b>Numéro de licence CAF :</b><br />
				<input type="text" name="cafnum_user" class="type1" value="<?php echo inputVal('cafnum_user', ''); ?>" placeholder="Requis" style="width:90%" />
			</div>

			<div class="tiers clear">
				<b>Civilité :</b><br />
				<select name="civ_user" class="type1" style="width:30%">
					<option value="M." <?php if (isset($_POST['civ_user']) && 'M.' == $_POST['civ_user']) {
					    echo 'selected="selected"';
					} ?>>M.</option>
					<option value="Mme." <?php if (isset($_POST['civ_user']) && 'Mme.' == $_POST['civ_user']) {
					    echo 'selected="selected"';
					} ?>>Mme.</option>
					<option value="Mlle." <?php if (isset($_POST['civ_user']) && 'Mlle.' == $_POST['civ_user']) {
					    echo 'selected="selected"';
					} ?>>Mlle.</option>
				</select>
			</div>

			<div class="tiers clear">
				<b>Nom :</b><br />
				<input type="text" name="lastname_user" class="type1" value="<?php echo inputVal('lastname_user', ''); ?>" placeholder="Requis" style="width:90%" />
			</div>
			<div class="tiers">
				<b>Prenom :</b><br />
				<input type="text" name="firstname_user" class="type1" value="<?php echo inputVal('firstname_user', ''); ?>" placeholder="Requis" style="width:90%" />
			</div>

			<div class="tiers clear">
				<b>Rôle sur cette sortie :</b><br />
				<select name="role_evt_join" class="type1" style="width:90%">
					<option value="manuel">Inscrit (par défaut)</option>
					<option value="benevole">Bénévole</option>
					<!-- <option value="coencadrant">Co-encadrant</option>
					<option value="encadrant">Encadrant</option> -->
				</select>
			</div>
			<div class="tiers">
				<b>Téléphone :</b><br />
				<input type="text" name="tel_user" class="type1" value="<?php echo inputVal('tel_user', ''); ?>" placeholder="Facultatif" style="width:90%" />
			</div>
			<div class="tiers">
				<b>Téléphone sécurité :</b><br />
				<input type="text" name="tel2_user" class="type1" value="<?php echo inputVal('tel2_user', ''); ?>" placeholder="Facultatif" style="width:90%" />
			</div>

			<div class="tiers">
				<b>Adresse email :</b><br />
				<input type="email" name="email_user" class="type1" value="<?php echo inputVal('email_user', ''); ?>" placeholder="Facultatif" style="width:90%" />
			</div>

			<br style="clear:both" />
			<br />
			<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
				<span class="bleucaf">&gt;</span>
				INSCRIRE CE MEMBRE NOMADE À MA SORTIE
			</a>

		</form>

		<!-- JS d'ergonomie -->
		<script type="text/javascript">
		$().ready(function(){
			// console.log(prefilled);

			// autofill des champs
			$('select[name=id_user]').bind('change', function(){
				var id_user = $(this).val();
				var index = $(this).find('option:selected').index('option')-1;
				var tmpPrefilled = prefilled[index];

				// quand on choisit une valeur
				if(id_user > 0){
					$('input[name=civ_user]').val(			tmpPrefilled.civ_user 		)			.attr('readonly', 'readonly');
					$('input[name=cafnum_user]').val(		tmpPrefilled.cafnum_user 		)		.attr('readonly', 'readonly');
					$('input[name=firstname_user]').val(	tmpPrefilled.firstname_user 		)	.attr('readonly', 'readonly');
					$('input[name=lastname_user]').val(		tmpPrefilled.lastname_user 		)		.attr('readonly', 'readonly');
					$('input[name=tel_user]').val(			tmpPrefilled.tel_user 		)			.attr('readonly', 'readonly');
					$('input[name=tel2_user]').val(			tmpPrefilled.tel2_user 		)			.attr('readonly', 'readonly');
					$('input[name=email_user]').val(		tmpPrefilled.email_user 		)		.attr('readonly', 'readonly');
				}
				// sinon : raz
				else{
					$('input').removeAttr('readonly');
				}
			});
		});
		</script>
		<?php
    }
}
