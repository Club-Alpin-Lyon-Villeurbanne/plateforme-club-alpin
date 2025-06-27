<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$article = false;
$errPage = false; // message d'erreur spécifique à la page courante si besoin
$id_article = (int) substr(strrchr($p2, '-'), 1);
$p_sitename = LegacyContainer::getParameter('legacy_env_SITENAME');

// sélection complète, non conditionnelle par rapport au status
$req = "SELECT a.*, c.title_commission, c.code_commission, m.filename
    FROM caf_article as a
    LEFT JOIN caf_commission as c ON a.commission_article = c.id_commission
    LEFT JOIN media_upload m ON a.media_upload_id = m.id
    WHERE id_article=$id_article
    LIMIT 1";
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // on a le droit de voir cet article ?
    if (1 == $handle['status_article'] // publié
        || ((allowed('article_validate_all') || allowed('article_validate')) && isset($_GET['forceshow']) && $_GET['forceshow']) // ou mode validateur
        || (user() && $handle['user_article'] == (string) getUser()->getId()) // ou j'en suis l'auteur
    ) {
        // auteur :
        $req = 'SELECT id_user, nickname_user
            FROM caf_user
            WHERE id_user=' . (int) $handle['user_article'] . '
            LIMIT 1';
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $handle['auteur'] = $handle2;
        }

        // info de la sortie liée
        if ($handle['evt_article'] > 0) {
            $req = 'SELECT e.code_evt, e.id_evt, e.titre_evt, c.title_commission
                FROM caf_evt as e
                LEFT JOIN caf_commission as c ON c.id_commission = e.commission_evt
                WHERE e.id_evt = ' . (int) $handle['evt_article'] . '
                LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['evt'] = $handle2;
            }
        }

        // commentaires
        $commentsTab = [];
        $req = "SELECT SQL_CALC_FOUND_ROWS *
            FROM caf_comment
            WHERE parent_type_comment='article'
            AND   parent_comment=$id_article
            AND   status_comment=1
            ORDER BY tsp_comment DESC
            LIMIT 50";
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // calcul du total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $totalComments = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            // infos user
            $req = 'SELECT nickname_user FROM caf_user WHERE id_user=' . (int) $handle2['user_comment'] . ' LIMIT 1';
            $handleSql3 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle3 = $handleSql3->fetch_array(\MYSQLI_ASSOC)) {
                $handle2['nickname_user'] = $handle3['nickname_user'];
            }

            // il est possible que l'user ait été supprimé. Dans ce cas :
            if ($handle2['user_comment'] > 0 && !$handle2['nickname_user']) {
                // on le traite comme un etranger
                $handle2['user_comment'] = 0;
            }

            $commentsTab[] = $handle2;
        }

        // MOdification des METAS de la page
        $meta_title = $handle['titre_article'] . ' | ' . $p_sitename;
        $meta_description = limiterTexte(strip_tags($handle['cont_article']), 200) . '...';
        // opengraphe : image pour les partages
        if ($handle['media_upload_id']) {
            $img = LegacyContainer::get('legacy_twig')->getExtension('App\Twig\MediaExtension')->getLegacyThumbnail(['filename' => $handle['filename']], 'wide_thumbnail');
        }

        // maj nb vues
        if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
            $req = "UPDATE caf_article SET nb_vues_article=nb_vues_article+1 WHERE id_article=$id_article AND status_article=1 LIMIT 1";
            LegacyContainer::get('legacy_mysqli_handler')->query($req);
        }

        // go
        $article = $handle;
    } else {
        $errPage = 'Accès non autorisé';
    }
}

?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<?php
        // article non trouvée, pas de message d'erreur, équivalent à un 404
        if (!$article && !$errPage) {
            echo '<br /><br /><br /><p class="erreur" style="margin:50px 20px 20px 20px">Hmmm... C\'est ennuyeux : nous n\'arrivons pas à trouver l\'article correspondant à cette URL.</p>';
        }
// article non trouvée, avec message d'erreur, tentative d'accès mesquine ou sortié dévalidée
if (!$article && $errPage) {
    echo '<br /><br /><br /><div class="erreur" style="margin:50px 20px 20px 20px">Erreur : Vous n\'avez pas accès à cette page. L\'article a peut-être été retiré par un responsable du site.</div>';
}

// article trouvée, pas d'erreur, affichage normal :
if ($article && !$errPage) {
    // FICHE DE LA article
    require __DIR__ . '/../includes/article-fiche.php';
}
?>
		<br style="clear:both" />
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>


	<br style="clear:both" />
</div>