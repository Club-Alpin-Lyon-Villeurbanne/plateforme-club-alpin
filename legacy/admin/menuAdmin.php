<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

if (isGranted(SecurityConstants::ROLE_ADMIN)) {
    ?>
	<div id="menuAdmin" style="<?php if ($p_pageadmin) {
	    echo 'position:relative';
	} ?>">
		<!-- specs -->
		<a href="<?php echo generateRoute('admin_logout'); ?>" title="" class="adminmenulink special"><img src="/img/base/door_out.png" alt="" title="" /> Déconnexion</a>
		<a href="/includer.php?p=includes/admin-log.php&admin=true" title="Voir les activités administrateur" class="adminmenulink special fancyframe"><img src="/img/base/report.png" alt="" title="" /> Log</a>
		<a href="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>" title="Retour au site" class="adminmenulink special"><img src="/img/base/house.png" alt="" title="" /> Retour au site</a>

		<span style="float:left">Menu administrateur : </span>

		<div style="margin-left:160px">
            <?php
	        foreach ($p_pages as $code => $datas) {
	            // pour chaque page admin seulement
	            if ($datas['menuadmin_page']) {// && !isContentManager()
	                // cas particulier : la page traductions seulement en cas de langues multiples
	                if ('admin-traductions' != $code) {
	                    echo '<a href="' . $code . '.html" title="" class="' . ($datas['superadmin_page'] ? 'superadmin ' : '') . ' adminmenulink ' . ($p1 == $code ? 'up' : '') . '">
                            ' . ($datas['superadmin_page'] ? '<img src="/img/base/bullet_star.png" alt="" title="Option super-admin" />' : '') . $datas['default_name_page'] . '</a>';
	                }
	            }
	        } ?>
		</div>
	</div>
	<?php
}
?>
