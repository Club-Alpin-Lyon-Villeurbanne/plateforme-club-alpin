<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use App\Helper\HtmlHelper;

$MAX_ADMINS_SUB_LEVELS = LegacyContainer::getParameter('legacy_env_MAX_ADMINS_SUB_LEVELS');

global $versCettePage;

if (isset($currentPage['admin_page']) && $currentPage['admin_page'] && !isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    echo 'Votre session administrateur a expiré ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    ?>
	<h2>Gestions des pages et de l'arborescence [en développement]</h2>

	<p>
		<b style="color:red">Important :</b> Ces options sont indissociables d'un travail de développeur sur les fichiers du site. En l'état actuel cette page NE PERMET PAS de créer
		physiquement de nouvelles pages, mais simplement de les organiser plus facilement dans la base de données. Seul le développeur devrait
		modifier les éléments présents ici.
	</p>
	<br />

	<a href="/includer.php?admin=true&p=pages/admin-pages-add.php" title="" class="fancyframe boutonFancy"><img src="/img/base/page_white_add.png" alt="" title="" /> Nouvelle page</a>

	<?php
    // TABLEAU D'ERREURS
    if (isset($_POST['operation']) && isset($errTab) && count($errTab) > 0) {
        echo '<div class="erreur">Erreur : <ul><li>- ' . implode('</li><li>- ', $errTab) . '</li></ul></div>';
    }
    if (isset($_POST['operation']) && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '.</p>';
    } ?>

	<div class="sortablepagelist niv0">
		<?php
        // requete de toutes les pages
    $req = 'SELECT * FROM  `caf_page` WHERE  `admin_page` =0  AND  `pagelibre_page` =0 ORDER BY  `parent_page` ASC, `ordre_menu_page` ASC LIMIT 0 , 300';
    $pageTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // Nom de la page dans chaque langue
        $handle['nom'] = [];

        $req = "SELECT contenu_content_inline FROM  `caf_content_inline` WHERE  `code_content_inline` LIKE 'meta-title-" . $handle['code_page'] . "' AND lang_content_inline LIKE 'fr' ORDER BY  `date_content_inline` DESC LIMIT 1";
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $tmp = '<i style="font-size: 0.6rem; color:red;">non défini</i>';

        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $tmp = $handle2['contenu_content_inline'];
        }
        $tmp = '<img src="/img/base/flag-fr.png" alt="fr" title="fr" style="height:17px; opacity:0.7; vertical-align:middle" /> ' . $tmp;
        $handle['nom'][] = $tmp;

        // ok, save
        $pageTab[] = $handle;
    }

    // fonction d'affichage par parent
    function listPages($tab, $parent, $level)
    {
        global $versCettePage;
        echo '<div class="sortablepagelist niv' . $level . '">';
        for ($i = 0; $i < count($tab); ++$i) {
            $page = $tab[$i];
            if ($page['parent_page'] == $parent) {
                echo '<div id="page_id_' . $page['id_page'] . '"" class="page-element niv' . $level . ' ' . ($page['vis_page'] ? '' : 'invisible') . '" style="' . ($parent ? 'margin-left:10px;' : '') . '">'
                        // Suppriemr
                        . ($page['lock_page'] ? '' : '<form action="' . $versCettePage . '" method="post" style="float:right"
							onsubmit="return(confirm(\'Voulez-vous vraiment supprimer définitivement cette page ? \n\n ' . addslashes($page['default_name_page']) . ' \'))">
							<input type="hidden" name="operation" value="page_del" />
							<input type="hidden" name="id_page" value="' . $page['id_page'] . '" />
							<input type="hidden" name="default_name_page" value="' . HtmlHelper::escape($page['default_name_page']) . '" />
							<input type="hidden" name="code_page" value="' . HtmlHelper::escape($page['code_page']) . '" />
							<input type="image" src="/img/base/x.png" class="upimage" alt="Supprimer" title="Supprimer" />
						</form>')
                        // deplacer
                        . '<div class="page-element-info-3"><img src="/img/base/move.png" alt="" title="Déplacer" style="height:16px" class="handle" /></div>'
                        . '<div class="page-element-separator"></div>'
                        // picto d'état vis
                        . ($page['vis_page']
                            ? '<div class="page-element-info-2 on"><img src="/img/base/vis-on.png" alt="MENU" title="Cette page est visible sur le site" /></div>'
                            : '<div class="page-element-info-2 off"><img src="/img/base/vis-off.png" alt="MENU" title="Cette page n\'apparaît PAS sur le site" /></div>'
                        )
                        // picto d'état menu
                        . ($page['menu_page']
                        ? '<div class="page-element-info-2 on"><img src="/img/base/chart_organisation.png" alt="MENU" title="Cette page apparaît dans le menu principal" /></div>'
                        : '<div class="page-element-info-2 off"><img src="/img/base/chart_organisation_delete.png" alt="MENU" title="Cette page n\'apparaît PAS dans le menu principal" /></div>'
                        )
                        // picto d'état priorité
                        . ($page['priority_page'] > 0
                        ? '<div class="page-element-info-2 on">' . round($page['priority_page'] * 10) . '<sub>/10</sub></div>'
                        : '<div class="page-element-info-2 off">Ø</div>'
                        )
                        // nifos texte
                        . '<div class="page-element-separator"></div>'
                        . '<div class="page-element-info-1">' . $page['code_page'] . '</div>'
                        // .'<div class="page-element-info-1">'.$page['default_name_page'].'</div>'
                        . '<div class="page-element-info-1">' . implode('<br />', $page['nom']) . '</div>'
                ;

                listPages($tab, $page['id_page'], $level + 1);
                // echo '<img src="/img/base/ghost.gif" alt="" title="" style="clear:both" />';
                echo '</div>';
            }
        }
        echo '</div>';
    }

    // GO
    listPages($pageTab, 0, 0); ?>
	</div>


	<!-- JS -->
	<script type="text/javascript">
	$().ready(function(){
		<?php
        if (isset($_GET['showmsg']) && 'page_add' == $_GET['showmsg']) {
            ?>
			lp_alert("<p>Vous venez de créer une nouvelle page. Celle-ci est <b>masquée</b> aux visiteurs du site, mais vous pouvez la visiter en tant qu'administrateur pour modifier les contenus nécessaires.</p><p>Une fois satisfait, <b>passez-là en ligne</b>.</p>");
			<?php
        } ?>

		function getDatas(){
			var data='';
			$('.page-element').each(function(){
				var id=$(this).attr('id').split('_');
				id=id[id.length-1];
				data=data+(data?'&':'')+'id[]='+id;
			});
			return data;
		}

		// sortables a tous les niveaux
		for(i=0; i<=<?php echo $MAX_ADMINS_SUB_LEVELS; ?>; i++){
			$('.sortablepagelist.niv'+i).sortable({
				items:'.page-element.niv'+i,
				handle:'.handle',
				connectWith:'.sortablepagelist.niv'+i, // liaisons avec pages de même niveau uniquement
				// connectWith:'.sortablepagelist',
				tolerance: 'intersect',
				placeholder: 'placeholder3',
				forcePlaceholderSize: true,
				stop:function(i){
					$.ajax({
						type: "GET",
						url: "/?ajx=pages_reorder",
						data: getDatas()
					})
				}
			});
		}
	});
	</script>

	<?php
}
