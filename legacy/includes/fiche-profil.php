<?php

use App\Entity\EventParticipation;
use App\Legacy\LegacyContainer;

// id du profil
$id_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) $_GET['id_user']);
$tmpUser = false;
$connectedUser = getUser();

if (!$connectedUser) {
    echo '<p class="erreur">Vous devez être connecté pour accéder à cette page</p>';
    exit;
}

$req = "SELECT * FROM caf_user WHERE id_user = $id_user LIMIT 1";
// AND valid_user = 1
// echo '<!-- '.$req.' -->';

$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($row = $result->fetch_assoc()) {
    // echo '<!-- FOUND -->';

    // debug
    if (1 == $row['birthday_user']) {
        $row['birthday_user'] = 0;
    }

    // liste des statuts
    $row['statuts'] = [];

    $req = 'SELECT title_usertype, params_user_attr, description_user_attr
		FROM caf_user_attr, caf_usertype
		WHERE user_user_attr=' . $id_user . '
		AND id_usertype=usertype_user_attr
		ORDER BY hierarchie_usertype DESC
		LIMIT 50';

    $result2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row2 = $result2->fetch_assoc()) {
        $commission = substr(strrchr($row2['params_user_attr'], ':'), 1);
        $row['statuts'][] = $row2['title_usertype'] . ($commission ? ', ' . $commission : '') . ($row2['description_user_attr'] ? '&nbsp;<img src="img/base/info.png" title="' . addslashes(html_utf8($row2['description_user_attr'])) . '">' : '');
    }

    $tmpUser = $row;
}

// err technique
if (!$id_user) {
    echo '<p class="erreur">ID non spécifié</p>';
}
// pas accessbile
elseif (!$tmpUser) {
    echo '<p class="erreur">Cette fiche utilisateur est introuvable !</p>';
}// droits !
else {
    // j'ai le droit de le contacter ?
    $auth_contact_user = false;
    if ('all' == $tmpUser['auth_contact_user']) {
        $auth_contact_user = true;
    }
    if ('users' == $tmpUser['auth_contact_user'] & user()) {
        $auth_contact_user = true;
    } ?>

	<!-- AVATAR-->
	<div style="float:left; padding:0 20px 30px 0; width:150px; overflow:hidden; text-align:center;">
		<img src="<?php echo userImg($id_user, 'min'); ?>" alt="" title="" style="max-width:100%" />

		<!-- fiche complète -->
		<?php
        if (allowed('user_read_limited')) {
            ?>
			<a href="/user-full/<?php echo $id_user; ?>.html" title="Quitter cette page pour voir la fiche complète de cet adhérent" class="nice2" target="_top">
				<img src="/img/base/user.png" alt="" title="" /> &nbsp; &nbsp; Profil complet
			</a>
			<?php
        } ?>

		<!-- contacter -->
		<?php
        if ($auth_contact_user) {
            echo '<a href="javascript:void(0)" title="Utiliser le formulaire de contact pour écrire un message à cet adhérent" class="nice2" onclick="$(\'#trigger-userinfo, #trigger-usercontact\').slideToggle()">
					<img src="/img/base/email.png" alt="" title="" /> &nbsp; &nbsp; Contacter
				</a>';
        } ?>

	</div>

	<!-- nick -->
	<div style="float:right; width:740px">
		<h1>
		<?php require __DIR__ . '/../includes/user/display_name.php'; ?>
		</h1>

		<!-- statuts -->
		<ul class="nice-list">
			<?php
            // if(allowed('user_read_limited')){
                foreach ($tmpUser['statuts'] as $status) {
                    echo '<li style="">' . $status . '</li>';
                }
    // } else {
    // echo '<li style="">Adhérent du club</li>';
    // }?>
		</ul>
		<br style="clear:both" />

		<!-- formulaire de contact -->
		<?php
        if ($auth_contact_user) {
            $contact_form_width = '50%';
            require __DIR__ . '/../includes/user/contact_form.php';
        } ?>

		<div id="trigger-userinfo" style="display:<?php if (isset($_POST['operation']) && 'user_contact' == $_POST['operation']) {
		    echo 'none';
		} ?>">




			<!-- infos persos-->
			<?php require __DIR__ . '/../includes/user/infos_privees.php'; ?>

            <?php
    // si j'ai acces ou si les données me concernent
    $isMyProfile = $connectedUser->getId() === (int) $id_user;
    if (allowed('user_read_private') || $isMyProfile) {
        list('absences' => $absences, 'presences' => $presences) = LegacyContainer::get('doctrine.orm.entity_manager')
            ->getRepository(EventParticipation::class)
            ->getEventPresencesAndAbsencesOfUser($id_user);
        echo '<p>';
        $total = $presences + $absences;
        $fiabilite = $total > 0 ? round(($presences / $total) * 100) : 100;
        printf('<b>Taux de présence: %d%% - (%d absences sur %d sorties)</b>', $fiabilite, $absences, $total);
        if ($isMyProfile) {
            echo '<br/>Ce taux donne une information sur le nombre d\'absences aux sorties auxquelles vous êtes inscrit.e.<br/>Il n\'est visible que par les encadrant.es.<br />Vous pouvez consulter la liste des sorties où vous avez été absent.e sur <a target="_parent"href="profil/sorties/self"/>la page de vos sorties</a>.';
        }
        echo '</p>';
    }
    ?>

			<br style="clear:both" />

            <?php $ecriture = get_niveaux($tmpUser['id_user'], true);
    $lecture = get_niveaux($tmpUser['id_user'], false);
    if ($lecture || $ecriture) {
        ?>
            <h2 id="niveaux"><span class="bleucaf">&gt;</span>Infos sur son niveau</h2>
            <?php
                if ($ecriture) {
                    echo "<p class='mini'>Vous pouvez éditer certaines informations depuis le profil complet de cet adhérent.</p>";
                }
        if (is_array($ecriture) && is_array($lecture)) {
            $lecture = array_merge($ecriture, $lecture);
        }
        if ($lecture) {
            display_niveaux($lecture, 'lecture');
        } ?>

			<br style="clear:both" />
            <?php
    } ?>

			<?php
            // REQUETES SQL POUR LES SORTIES :
            display_sorties($id_user, 3, 'Dernières sorties');
    // REQUETES SQL POUR LES ARTICLES :
    display_articles($id_user, 6, 'Derniers articles'); ?>
		</div>
	</div>

	<?php
}
