<?php

use App\Entity\EventParticipation;
use App\Legacy\LegacyContainer;

// id du profil
$id_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) $_GET['id_user']);
$tmpUser = $tmpEvent = $tmpArticle = false;
$connectedUser = getUser();

$idEvent = null;
if (array_key_exists('id_event', $_GET) && !empty($_GET['id_event'])) {
    $idEvent = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) $_GET['id_event']);
}

$idArticle = null;
if (array_key_exists('id_article', $_GET) && !empty($_GET['id_article'])) {
    $idArticle = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) $_GET['id_article']);
}

if (!$connectedUser) {
    echo '<p class="erreur">Vous devez être connecté pour accéder à cette page</p>';
    exit;
}

$req = "SELECT * FROM caf_user WHERE id_user = $id_user LIMIT 1";
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($row = $result->fetch_assoc()) {
    // debug
    if (1 == $row['birthday_user']) {
        $row['birthday_user'] = 0;
    }

    require __DIR__ . '/../includes/user/statuts.php';

    $tmpUser = $row;
}

if (!empty($idEvent)) {
    $eventReq = "SELECT * FROM caf_evt WHERE id_evt = $idEvent LIMIT 1";
    $eventResult = LegacyContainer::get('legacy_mysqli_handler')->query($eventReq);
    while ($eventRow = $eventResult->fetch_assoc()) {
        $tmpEvent = $eventRow;
    }
}

if (!empty($idArticle)) {
    $articleReq = "SELECT * FROM caf_article WHERE id_article = $idArticle LIMIT 1";
    $articleResult = LegacyContainer::get('legacy_mysqli_handler')->query($articleReq);
    while ($articleRow = $articleResult->fetch_assoc()) {
        $tmpArticle = $articleRow;
    }
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
        <br style="clear:left;" />

		<!-- statuts -->
        <?php require __DIR__ . '/../includes/user/display_status.php'; ?>

		<!-- formulaire de contact -->
		<?php
        if ($auth_contact_user) {
            $contact_form_width = '50%';
            require __DIR__ . '/../includes/user/contact_form.php';
        } ?>

		<div id="trigger-userinfo" style="display:<?php if (isset($_POST['operation']) && 'user_contact' == $_POST['operation']) {
		    echo 'none';
		} ?>">

			<!-- infos persos réduites -->
            <?php
		    if (allowed('user_read_private')) {
		        echo '<hr  />'
		             . '<h3>Infos privées : </h3>'
		             . '<ul class="nice-list">'
		             . '<li>NUMÉRO DE LICENCE FFCAM : ' . html_utf8($tmpUser['cafnum_user']) . '</a> </li>';
		        if (allowed('user_read_private') && $tmpUser['doit_renouveler_user']) {
		            echo '<li class="red">LICENCE EXPIRÉE</li>';
		        } elseif (allowed('user_read_private') && !empty($tmpUser['date_adhesion_user'])) {
		            echo '<li>DATE D\'ADHÉSION : <span class="green">' . date('d/m/Y', $tmpUser['date_adhesion_user']) . '</span></li>';
		        }
		        echo '<li><a href="mailto:' . html_utf8($tmpUser['email_user']) . '" title="Contact direct">' . html_utf8($tmpUser['email_user']) . '</a> </li>'
		             . '</ul>'
		             . '<br style="clear:both" />'
		        ;
		    }
    ?>

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
            echo '<br/>Ce taux donne une information sur le nombre d\'absences aux sorties auxquelles vous êtes inscrit.e.<br/>Il n\'est visible que par les encadrant.es.<br />Vous pouvez consulter la liste des sorties où vous avez été absent.e sur <a target="_parent" href="profil/sorties/prev"/>la page de vos sorties passées</a>.';
        }
        echo '</p>';
    }
    ?>

			<br style="clear:both" />
			<?php
            // REQUETES SQL POUR LES SORTIES :
            display_sorties($id_user, 3, 'Dernières sorties');
    // REQUETES SQL POUR LES ARTICLES :
    display_articles($id_user, 6, 'Derniers articles'); ?>
		</div>
	</div>

	<?php
}
