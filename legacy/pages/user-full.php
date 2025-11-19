<?php

use App\Entity\EventParticipation;
use App\Legacy\LegacyContainer;

// id du profil
$id_user = (int) $p2;
$tmpUser = $tmpEvent = $tmpArticle = false;
$connectedUser = getUser();

$req = "SELECT * FROM caf_user WHERE id_user = $id_user AND is_deleted = 0 LIMIT 1";

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
            if (!$connectedUser) {
                echo '<br /><br /><p class="erreur">Vous devez être connecté pour accéder à cette page.</p>';
            } elseif (!allowed('user_read_limited')) {
                echo '<br /><br /><p class="erreur">Désolé. Vous n\'avez pas les droit requis pour afficher cette page.</p>';
            } elseif (!$tmpUser) {
                echo '<br /><br /><p class="erreur">Cet utilisateur est introuvable.</p>';
            } else {
                // j'ai le droit de le contacter ?
                $auth_contact_user = true;
                ?>

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
				</div>
                <br style="clear:both" />
                <hr  />

                <?php
                // si j'ai acces ou si les données me concernent
                $isMyProfile = getUser()->getId() === (int) $id_user;
                if (allowed('user_read_private') || $isMyProfile) {
                    ['absences' => $absences, 'presences' => $presences] = LegacyContainer::get('doctrine.orm.entity_manager')
                      ->getRepository(EventParticipation::class)
                      ->getEventPresencesAndAbsencesOfUser($id_user)
                    ;
                    echo '<p>';
                    $total = $presences + $absences;
                    $fiabilite = $total > 0 ? round(($presences / $total) * 100) : 100;
                    printf('<b>Taux de présence : %d%% - (%d absences sur %d sorties)</b>', $fiabilite, $absences, $total);
                    if ($isMyProfile) {
                        echo '<br/>Ce taux donne une information sur le nombre d\'absences aux sorties auxquelles vous êtes inscrit.e.<br/>Il n\'est visible que par les encadrant.es.<br />Vous pouvez consulter la liste des sorties où vous avez été absent.e sur <a href="profil/sorties/prev"/>la page de vos sorties passées</a>.';
                    }
                    echo '</p>';
                }
                ?>
				<br style="clear:both" />
				<hr  />

				<?php
                // REQUETES SQL POUR LES SORTIES :
                display_sorties($id_user, 200, 'Ses sorties');
                ?>

            <br style="clear:both" />
            <hr  />
            <?php
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