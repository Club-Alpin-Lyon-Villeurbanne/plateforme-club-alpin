<?php

use App\Legacy\LegacyContainer;

// id du profil
$id_user = (int) $p2;
$tmpUser = $tmpEvent = $tmpArticle = false;

$req = "SELECT * FROM caf_user WHERE id_user = $id_user LIMIT 1";

// AND valid_user = 1
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($row = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    require __DIR__ . '/../includes/user/statuts.php';

    $tmpUser = $row;
}
?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div style="padding:20px">
			<?php
            if (!allowed('user_read_limited')) {
                echo '<br /><br /><p class="erreur">Désolé. Vous n\'avez pas les droit requis pour afficher cette page.</p>';
            } elseif (!$tmpUser) {
                echo '<br /><br /><p class="erreur">Cet utilisateur est introuvable.</p>';
            } else {
                // j'ai le droit de le contacter ?
                $auth_contact_user = false;
                if ('all' == $tmpUser['auth_contact_user']) {
                    $auth_contact_user = true;
                }
                if ('users' == $tmpUser['auth_contact_user'] & user()) {
                    $auth_contact_user = true;
                } ?>

				<!-- AVATAR-->
				<img src="<?php echo userImg($id_user, 'big'); ?>" alt="" title="" style="float:left; width:35%; box-shadow: 0 0 12px -5px gray; border: 1px solid white;" />

				<div style="float:right; width:62%">

					<!-- contacter -->
					<?php
                    if (user() && $auth_contact_user) {
                        echo '<a href="javascript:void(0)" title="Utiliser le formulaire de contact pour écrire un message à cet adhérent" class="nice2" style="float:right" onclick="$(\'#trigger-userinfo, #trigger-usercontact\').slideToggle()">
								<img src="/img/base/email.png" alt="" title="" /> &nbsp; &nbsp; Contacter
							</a>';
                    } ?>

					<!-- nick -->
					<h1 style="padding:13px 0 0 0">
						<?php require __DIR__ . '/../includes/user/display_name.php'; ?>
					</h1>
                    <br style="clear:left;" />

					<!-- formulaire de contact -->
					<?php
                    if ($auth_contact_user) {
                        $contact_form_width = '95%';
                        require __DIR__ . '/../includes/user/contact_form.php';
                    } ?>

                    <!-- statuts -->
                    <?php require __DIR__ . '/../includes/user/display_status.php'; ?>

					<!-- infos persos-->
					<?php require __DIR__ . '/../includes/user/infos_privees.php'; ?>

					<br />
				</div>
				<br style="clear:both" />
				<hr  />

				<?php
                // REQUETES SQL POUR LES SORTIES :
                display_sorties($id_user, 200, 'Ses sorties');
                // REQUETES SQL POUR LES ARTICLES :
                display_articles($id_user, 200, 'Ses articles');
            }
?>
			<br style="clear:both" />
		</div>
	</div>

    <?php require __DIR__ . '/../includes/right-type-agenda.php'; ?>


	<br style="clear:both" />
</div>