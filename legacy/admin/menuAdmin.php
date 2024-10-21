<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


$isAdmin = isGranted(SecurityConstants::ROLE_ADMIN);
$isContentManager = isGranted(SecurityConstants::ROLE_CONTENT_MANAGER);
$allowedContentManagerPages = ['admin-partenaires', 'admin-contenus', 'admin-pages-libres'];

?>
<div id="menuAdmin" style="position:relative">
	<!-- specs -->
	<a href="<?php echo generateRoute('admin_logout'); ?>" title="" class="adminmenulink special"><img src="/img/base/door_out.png" alt="" title="" /> Déconnexion</a>
	<a href="/includer.php?p=includes/admin-log.php&admin=true" title="Voir les activités administrateur" class="adminmenulink special fancyframe"><img src="/img/base/report.png" alt="" title="" /> Log</a>
	<a href="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>" title="Retour au site" class="adminmenulink special"><img src="/img/base/house.png" alt="" title="" /> Retour au site</a>

	<span style="float:left">Menu <?php echo $isAdmin ? 'Administrateur' : 'Gestionnaire Contenu'; ?> : </span>

	<div style="margin-left:160px">
		<?php

		foreach ($p_pages as $codePage => $datas) {
			if ($datas['menuadmin_page'] && 'admin-traductions' != $codePage) {
				if ($isAdmin || in_array($codePage, $allowedContentManagerPages)) {
					echo '<a href="' . $codePage . '.html" title="" class="adminmenulink ' . ($p1 == $codePage ? 'up' : '') . '">
						' . $datas['default_name_page'] . '</a>';
				}
			}
		}
		?>
	</div>
	<div style="clear:both"></div>
</div>