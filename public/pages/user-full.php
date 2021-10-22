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
                    if ($auth_contact_user) {
                        echo '<a href="javascript:void(0)" title="Utiliser le formulaire de contact pour écrire un message à cet adhérent" class="nice2" style="float:right" onclick="$(\'#trigger-userinfo, #trigger-usercontact\').slideToggle()">
								<img src="img/base/email.png" alt="" title="" /> &nbsp; &nbsp; Contacter
							</a>';
                    } ?>

					<!-- nick -->
					<h1 style="padding:13px 0 0 0">
						<?php include INCLUDES.'user'.DS.'display_name.php'; ?>
					</h1>

					<!-- formulaire de contact -->
					<?php
                    if ($auth_contact_user) {
                        $contact_form_width = '95%';
                        include INCLUDES.'user'.DS.'contact_form.php';
                    } ?>

					<!-- statuts -->
					<ul class="nice-list">
						<?php
                        foreach ($tmpUser['statuts'] as $status) {
                            echo '<li style="">'.$status.'</li>';
                        } ?>
						<li><a href="<?php echo $versCettePage; ?>#user-sorties" title="">Voir ses sorties</a></li>
						<li><a href="<?php echo $versCettePage; ?>#user-articles" title="">Voir ses articles</a></li>
					</ul>
					<br style="clear:left;" />

					<!-- infos persos-->
					<?php include INCLUDES.'user'.DS.'infos_privees.php'; ?>

					<br />
				</div>

                <?php
                    $ecriture = get_niveaux($id_user, true);
                $lecture = get_niveaux($id_user, false);
                if ($ecriture || $lecture) {
                    echo '<br style="clear:both" /><hr /><h2>Son niveau</h2>';
                }
                if ($ecriture) {
                    echo '<form method="post" action="'.$versCettePage.'" class="hover">';
                    echo '<input type="hidden" name="operation" value="niveau_update" >';
                    display_niveaux($ecriture, 'ecriture');
                    echo '<div style="text-align:center"><a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents(\'form\').submit()"><span class="bleucaf">&gt;</span>ENREGISTRER LES NIVEAUX</a></div>';
                    echo '</form>';
                }
                if ($lecture) {
                    display_niveaux($lecture, 'lecture', $ecriture);
                } ?>

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

	<!-- partie droite -->
	<div id="right1">
		<div class="right-light">
			&nbsp; <!-- important -->
			<?php
            // RECHERCHE
            include INCLUDES.'recherche.php';
            ?>
		</div>


		<div class="right-green">
			<div class="right-green-in">

				<?php
                // AGENDA sur fond vert
                include INCLUDES.'droite-agenda.php';
                ?>

			</div>
		</div>

	</div>


	<br style="clear:both" />
</div>