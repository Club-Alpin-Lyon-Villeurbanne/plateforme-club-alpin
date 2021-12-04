<?php

if (admin()) {
    ?>
	<div id="menuAdmin" style="<?php if ($p_pageadmin) {
        echo 'position:relative';
    } ?>">
		<!-- specs -->
		<a href="<?php echo generateRoute('admin_logout'); ?>" title="" class="adminmenulink special"><img src="/img/base/door_out.png" alt="" title="" /> Déconnexion</a>
		<a href="includer.php?p=includes/admin-log.php&admin=true" title="Voir les activités administrateur" class="adminmenulink special fancyframe"><img src="/img/base/report.png" alt="" title="" /> Log</a>
		<a href="<?php echo $p_racine; ?>" title="Retour au site" class="adminmenulink special"><img src="/img/base/house.png" alt="" title="" /> Retour au site</a>

		<span style="float:left">Menu administrateur : </span>

		<div style="margin-left:160px">

			<?php
            // menu
            $i = 0;
    foreach ($p_pages as $code => $datas) {
        // pour chaque page admin seulement
        if ($datas['menuadmin_page']) {
            // si c'est nue page reservee au superadmin, on ne la propose pas
            if (!$datas['superadmin_page'] || superadmin()) {
                // cas particulier : la page traductions seulement en cas de langues multiples
                if ('admin-traductions' != $code || count($p_langs) > 1) {
                    echo '<a href="'.$code.'.html" title="" class="'.($datas['superadmin_page'] ? 'superadmin ' : '').' adminmenulink '.($p1 == $code ? 'up' : '').'">
								'.($datas['superadmin_page'] ? '<img src="/img/base/bullet_star.png" alt="" title="Option super-admin" />' : '').$datas['default_name_page'].'</a>';
                    ++$i;
                }
            }
        }
    } ?>

			<!--
			<a href="admin-contenus.html" title="" class="adminmenulink <?php if ('admin-contenus' == $p1) {
        echo 'up';
    } ?>">Contenus statiques</a>
			<?php if (count($p_langs) > 1) { ?>
				<a href="admin-traductions.html" title="" class="adminmenulink <?php if ('admin-traductions' == $p1) {
        echo 'up';
    }?>">Traductions</a>
			<?php } ?>

			<a href="admin-actus.html" title="" class="adminmenulink <?php if ('admin-actus' == $p1) {
        echo 'up';
    } ?>">Actualités</a>
			<a href="admin-reas.html" title="Organisez vos réalisations" class="adminmenulink <?php if ('admin-reas' == $p1) {
        echo 'up';
    } ?>">Réalisations</a>
			<a href="admin-partenaires.html" title="Organisez l'onglet valeurs en pied de page" class="adminmenulink <?php if ('admin-partenaires' == $p1) {
        echo 'up';
    } ?>">Onglet "Partenaires"</a>
			<a href="admin-newsletter.html" title="Inscrits à la newsletter" class="adminmenulink <?php if ('admin-newsletter' == $p1) {
        echo 'up';
    } ?>">Newsletter</a>
			-->

		</div>
		<div style="clear:both" >
			<!--[if IE]>
			<p class="erreur" style="margin:40px 20px 20px 20px; font-size:12px; font-family:Arial;">
				<span style="height:0px; width:10px; text-align:left;  float:right">
					<img src="/img/base/x.png" style="padding:3px; cursor:pointer; " alt="" title="Fermer" onclick="$(this).parents('p').fadeOut();" />
				</span>
				Attention, vous utilisez actuellement <b>Internet explorer</b> pour votre session administrateur, ce qui est déconseillé. <br />
				Pour une utlisation à la fois plus agréable et plus sécurisante de votre espace d'administration, nous vous proposons
				d'utiliser <a href="https://www.mozilla.org/fr/firefox/new/" title="Page de téléchargement de Firefox" class="blank">Mozilla Firefox</a>,
				<a href="https://www.google.com/chrome?hl=fr" title="Page de téléchargement de Google Chrome" class="blank">Google Chrome</a>,
				ou <a href="https://www.apple.com/fr/safari/" title="Page de téléchargement de Safari" class="blank">Safari</a>.<br />
				Ces navigateurs sont davantages à jour technologiquement, plus rapides, et respectent les standards et l'éthique du web.
			</p>
			<![endif]-->
		</div>
	</div>
	<?php
}
?>
