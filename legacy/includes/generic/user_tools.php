<a id="toolbar-user" href="javascript:void(0)" title="" class="toptrigger">
	<?php
    // NON CONNECTÉ
    if (!user()) {
        ?>
		<!-- trigger -->
		<span class="picto"><img src="/img/toolbox.png" alt="" title="" class="light" /><img src="/img/toolbox-up.png" alt="" title="" class="dark" /></span> Espace<br /><b>Adhérents</b>
		<span id="shadowcache-user" class="shadowcache"></span>
		<?php
    }

    // CONNECTÉ
    else {
        ?>
		<div class="connected-name">
			<?php
            // notifications ? Définies dans SCRIPTS.'reqs.php'

            // notification d'alerte si l'user doit renouveler sa licence
            if ($_SESSION['user']['doit_renouveler_user'] || $_SESSION['user']['alerte_renouveler_user']) {
                echo '<div class="notification-user-alerte"><span>!</span></div>';
            }

        // nombre total de notifications :
            $totalNotif = $notif_validerunesortie + $notif_validerunesortie_president + $notif_validerunarticle + $notif_publier_destination; // ajouter toutes les notifications necessaires...

            if ($totalNotif > 0) {
                echo '<div class="notification-user"><span>'.$totalNotif.'</span></div>';
            } ?>
			<img src="<?php echo userImg($_SESSION['user']['id_user'], 'pic').'?ac='.time(); ?>" alt="" title="" />
			<p>
				<?php /* Bonjour <br /><b><?php echo html_utf8($_SESSION['user']['civ_user']).'&nbsp;'.html_utf8($_SESSION['user']['lastname_user']);?></b> */ ?>
				Bonjour <?//php echo html_utf8($_SESSION['user']['civ_user']);?><br />
				<b><?php echo html_utf8($_SESSION['user']['firstname_user']); ?>,</b>
				<span id="shadowcache-user" class="shadowcache" style="top:8px;"></span>
			</p>
		</div>
		<?php
    }
    ?>
</a>



<!-- navigation adherent -->
<nav id="toolbar-user-hidden">
	<div class="sitewidth">
		<img src="/img/bg-usermenu.png" alt="" title="" style="float:left; padding:30px 30px 30px 0" />
		<?php
        // NON CONNECTÉ
        if (!user()) {
            ?>
			<!-- creer un compte (vers page profil) -->
			<div style="width:515px; float:left; border-right:1px solid #c6e39f; min-height:120px; padding:0px 5px 0 0">
				<?php inclure('mainmenu-creer-mon-compte', 'menucontent'); ?>
				<a class="nice2" href="profil.html" title="">Activer mon compte</a>
			</div>

			<!-- connexion ajax (reste sur la même page) -->
			<!-- la class AJAXFORM fonctionne sur un modèle défini dans js/onready-site.js -->
			<!-- les messages de reponse se trouvent dans SCRIPTS.'operations.php' -->
			<form class="menucontent ajaxform" autocomplete="on" action="profil.html" method="post" style="width:290px; padding-right:10px; float:right">
				<input type="hidden" name="operation" value="user_login">

				<?php inclure('mainmenu-connection', 'menucontent'); ?>

				Votre e-mail<br />
				<input type="text" name="email_user" class="type1" value="" placeholder="" autocomplete="on">
				<br />

				Votre mot de passe<br />
				<input type="password" name="mdp_user" class="type1" value="" placeholder="" autocomplete="off">

				<!-- <a href="javascript:void(0)" title="" onclick="$(this).parents('form').submit()" class="nice2">Connexion</a> -->
				<input type="submit"  class="nice2" value="Connexion" onclick="$(this).parents('form').submit()" />
				<br />

				<a href="includer.php?p=pages/mot-de-passe-perdu.php" class="fancyframe" title="" style="font-size:10px; position:relative; bottom:4px; font-family:Arial; color:white; font-weight:100; opacity:0.7;">Mot de passe oublié ?</a>

				<div class="error_reporting" style="display:none"></div>

			</form>


			<?php
        }

        // CONNECTÉ
        else {
            // MESSAGE D'ALERTE SI CET USER EST VERROUILLE POUR LICENCE EXPIREE
            if ($_SESSION['user']['doit_renouveler_user']) {
                echo '<div style="padding:5px 0 30px 100px">';
                inclure('alerte-licence-obsolete', 'alerte');
                echo '</div>';
            } elseif ($_SESSION['user']['alerte_renouveler_user']) {
                // MESSAGE D'ALERTE SI CET USER DOIT RENOUVELER SA LICENCE AVANT BIENTOT
                echo '<div style="padding:5px 0 30px 100px">';
                inclure('alerte-licence-renouveler', 'alerte');
                echo '</div>';
            } ?>
			<div style="width:515px; min-height:160px; float:left; border-right:1px solid #c6e39f; min-height:130px; padding:0px 5px 0 0">

				<div class="nav-user">
					<p class="menutitle">Mes sorties</p>

					<?php
                    // - publier une sortie (notification au besoin, variable définie dans SCRIPTS.'reqs.php')
                    if (allowed('evt_validate')) {
                        echo '<a href="gestion-des-sorties.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>'.($notif_validerunesortie ? '<span class="notification">'.$notif_validerunesortie.'</span>' : '').'publication des sorties</a>';
                    }
            // - valider juridiquement une sortie
            if (allowed('evt_legal_accept')) {
                echo '<a href="validation-des-sorties.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>'.($notif_validerunesortie_president ? '<span class="notification">'.$notif_validerunesortie_president.'</span>' : '').'validation des sorties</a>';
            }

            if (allowed('destination_creer') || allowed('destination_modifier')) {
                echo '<a href="profil/destinations.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>'.($notif_publier_destination ? '<span class="notification">'.$notif_publier_destination.'</span>' : '').'destinations</a><br />';
            }
            // - créer une sortie (par défaut : pour la commission courante si autorisé)
            if (allowed('evt_create')) {
                echo '<a href="creer-une-sortie'/*.($current_commission && allowed('evt_create', 'commission:'.$current_commission)?'/'.$current_commission:'')*/.'.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>proposer une sortie</a>';
            }
            // - les sorties que j'ai créé
            if (allowed('evt_create')) {
                echo '<a href="profil/sorties/self.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>sorties que j\'organise</a><br />';
            } ?>

					<a href="profil/sorties/next.html" title="">mes sorties à venir</a><br />
					<a href="profil/sorties/prev.html" title="">mes sorties passées</a><br />
				</div>

				<div class="nav-user">
					<p class="menutitle">Mon profil</p>

					<a href="profil/infos.html" title="">mes infos publiques</a><br />
					<a href="profil/infos.html#private" title="">mes infos privées</a><br />
					<!--<a href="profil/filiations.html" title="">filiations</a>-->
				</div>

				<div class="nav-user">
					<p class="menutitle">Articles</p>

					<?php
                    // - mes articles
                    if (allowed('article_create')) {
                        echo '<a href="article-new.html'.($current_commission ? '?commission='.$current_commission : '').'" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>rédiger un article</a>';
                    }
            if (allowed('article_create')) {
                echo '<a href="profil/articles.html'.($current_commission ? '?commission='.$current_commission : '').'" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>mes articles</a>';
            }
            // - valider un article / modérer
            if (allowed('article_validate') || allowed('article_validate_all')) {
                echo '<a href="gestion-des-articles.html'.($current_commission ? '?commission='.$current_commission : '').'" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>'.($notif_validerunarticle ? '<span class="notification">'.$notif_validerunarticle.'</span>' : '').'validation / gestion des articles</a>';
            } ?>
					<a href="recherche.html" title="">rechercher un article</a><br />

				</div>

				<br  style="clear:both" />
				<?php
                // admins: adherents
                if (allowed('user_see_all')
                        || allowed('user_create_manually')
                        || allowed('user_updatefiles')
                        ) {
                    ?>
					<div class="nav-user">
						<p class="menutitle" style="padding-top:13px;">Adhérents</p>
						<?php

                        // - mise à jour du fichier
                        if (allowed('user_updatefiles') && $p_user_updatefiles) {
                            echo '<a href="fichier-adherents.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>mise à jour du fichier adhérents</a>';
                        }
                    /* Supprimer également l'entrée de caf-page avec la clé fichier-adherents */
                    // - voir tous les adhérents
                    if (allowed('user_see_all')) {
                        echo '<a href="adherents.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>gestion des adhérents</a>';
                    }

                    // - Créer un compte édhérent
                    if (allowed('user_create_manually')) {
                        echo '<a href="adherents-creer.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>créer un adhérent/salarié</a>';
                    } ?>
					</div>
					<?php
                } ?>

				<?php
                // admins: commission
                if (allowed('comm_create')
                    || allowed('comm_edit')
                    || allowed('comm_desactivate')
                    || allowed('comm_delete')
                    ) {
                    ?>
					<div class="nav-user">
						<p class="menutitle" style="padding-top:13px;">Commissions</p>
						<?php

                        // - gérer les commissions
                        if (allowed('comm_edit')) {
                            echo '<a href="gestion-des-commissions.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>gestion des commissions</a>';
                        }

                    // - creer une commission
                    if (allowed('comm_create')) {
                        echo '<a href="commission-add.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>créer une commission</a>';
                    } ?>
					</div>
					<?php
                } ?>

				<?php
                // admins: stats
                if (allowed('stats_commissions_read')
                    || allowed('stats_users_read')
                    ) {
                    ?>
					<div class="nav-user">
						<p class="menutitle" style="padding-top:13px;">Statistiques</p>
						<?php

                        // - gérer les commissions
                        if (allowed('stats_commissions_read')) {
                            echo '<a href="stats/commissions.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>statistiques par sorties</a>';
                        }

                    // - creer une commission
                    if (allowed('stats_users_read')) {
                        echo '<a href="stats/users.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>statistiques par adhérents</a>';
                    }

                    // - stats sur les articles
                    if (allowed('article_create')) {
                        echo '<a href="stats/nbvues.html" title=""><span class="star"><img src="/img/base/bullet_star.png" alt="" title="" /></span>statistiques articles</a>';
                    } ?>
					</div>
					<?php
                } ?>

			</div>

			<div style="width:340px; float:right; padding:0px 10px 0 0; ">

				<img src="<?php echo userImg($_SESSION['user']['id_user'], 'min').'?ac='.time(); ?>" alt="" title="" style="float:left; padding:0 10px 0 0;" />

				<p style="color:#fff; font-family:DIN; font-size:18px; line-height:20px; white-space:nowrap; padding-top:10px;">
					Mon pseudonyme : <br />
					<span style="font-family:DINBold; font-size:21px;"><?php echo html_utf8($_SESSION['user']['nickname_user']); ?></span>
				</p>


				<!-- deconn -->
				<a href="accueil.html?user_logout=true" title="" class="nice2">
					Me déconnecter
				</a>
				<br />
				<!-- compte -->
				<a href="profil.html" title="" class="nice2">
					Mon compte
				</a>

				<?php
                // affichage du plus haut statut. défini dans APP . 'fonctions.php' > fonction user_login()
                if (count($_SESSION['user']['status'])) {
                    // un detail, si le type le plus haut est lié à une commission (Encadrant, Resp. de commission, Rédacteur)
                    // il faut faire remonter en premier la version de ce type liée à la commission courante.
                    if (preg_match("#Encadrant|Resp\. de commission|Rédacteur#", $_SESSION['user']['status'][0])) {
                        // statut...
                        $statut = strtolower(substr(strstr($_SESSION['user']['status'][0], '.'), 0));
                        // si ce type existe dans le tableau pour la commission visée, on supprime l'entrée visée pour la remettre au début du tableau
                        if (in_array($statut.', '.$p2, $_SESSION['user']['status'], true)) {
                            if (($key = array_search($statut.', '.$p2, $_SESSION['user']['status'], true)) !== false) {
                                unset($_SESSION['user']['status'][$key]);
                                array_unshift($_SESSION['user']['status'], $statut.', '.$p2);
                            }
                        }
                    }
                    echo '<p class="status">';
                    echo 'Vous êtes <span style="font-family:DINBold">'.$_SESSION['user']['status'][0].'</span>';
                    if (count($_SESSION['user']['status']) > 1) {
                        echo ' <a href="profil.html" title="Entre autres choses..." style="color:white; font-weight:100; font-family:Arial">[+]</a>';
                    }
                    echo '</p>';
                } else {
                    echo '<p class="status">Vous êtes connecté en tant qu\'<span style="font-family:DINBold">adhérent</span></p>';
                } ?>
			</div>

			<?php
        }
        ?>
		<br style="clear:both" />
	</div>
</nav>
