<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

?>
<div id="menuAdmin" style="position:relative">
	<!-- specs -->
	<a href="<?php echo generateRoute('admin_logout'); ?>" title="" class="adminmenulink special"><img src="/img/base/door_out.png" alt="" title="" /> Déconnexion</a>
	<a href="/includer.php?p=includes/admin-log.php&admin=true" title="Voir les activités administrateur" class="adminmenulink special fancyframe"><img src="/img/base/report.png" alt="" title="" /> Log</a>
	<a href="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>" title="Retour au site" class="adminmenulink special"><img src="/img/base/house.png" alt="" title="" /> Retour au site</a>

	<span style="float:left">Menu Gestionnaire Contenu : </span>

	<div style="margin-left:160px">
		<?php
		foreach ($p_pages as $codePage => $datas) {
			// pour chaque page admin seulement
			if ($datas['menuadmin_page'] && ($codePage == 'admin-partenaires' || $codePage == 'admin-contenus' || $codePage == 'admin-pages-libres')) {
				// cas particulier : la page traductions seulement en cas de langues multiples
				if ('admin-traductions' != $codePage) {
					echo '<a href="' . $codePage . '.html" title="" class="adminmenulink ' . ($p1 == $codePage ? 'up' : '') . '">
						' . $datas['default_name_page'] . '</a>';
				}
			}
		} ?>
	</div>
	<div style="clear:both" ></div>
</div>