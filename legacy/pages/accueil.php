<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$MAX_ARTICLES_ACCUEIL = LegacyContainer::getParameter('legacy_env_MAX_ARTICLES_ACCUEIL');
$p_sitename = LegacyContainer::getParameter('legacy_env_SITENAME');

$sliderTab = [];
$articlesTab = [];

// *******************
// articles dans le slider
$req = 'SELECT a.`id_article`, a.`tsp_crea_article`, a.`tsp_article`, a.`user_article`, a.`titre_article`,
        a.`code_article`, a.`commission_article`, a.`media_upload_id`, m.`filename`
	FROM `caf_article` a
	LEFT JOIN `media_upload` m ON a.`media_upload_id` = m.`id`
	WHERE a.`status_article` = 1
	AND a.`une_article` = 1
	ORDER BY a.`tsp_validate_article` DESC
	LIMIT 0, 5';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    $sliderTab[] = $handle;
}

// *******************
// articles dans la page
$limite = $MAX_ARTICLES_ACCUEIL; // nombre d'elements affiches
$pagenum = (int) ($_GET['pagenum'] ?? 0);
if ($pagenum < 1) {
    $pagenum = 1;
} // les pages commencent à 1

// premiere requete : défaut, ne considère pas les articles liés à une sortie, liée à la commission courante
$select = 'id_article , status_article ,  status_who_article ,  tsp_article ,  user_article ,  titre_article ,  code_article ,  evt_article ,  une_article ,  cont_article , media_upload_id, filename, c.code_commission, c.title_commission';
$req = 'SELECT SQL_CALC_FOUND_ROWS ' . $select . '
	FROM  caf_article
    LEFT JOIN caf_evt as e ON (e.id_evt = caf_article.evt_article)
    INNER JOIN caf_commission as c ON ((e.commission_evt = c.id_commission OR caf_article.commission_article = c.id_commission) ';
if ($current_commission) {
    $req .= ' AND c.id_commission = ' . (int) $comTab[$current_commission]['id_commission'];
}
$req .= ')
	LEFT JOIN media_upload m ON caf_article.media_upload_id = m.id
	WHERE  status_article =1
	';
// commission donnée : filtre (mais on inclut les actus club, commission=0)
$req .= ' ORDER BY  tsp_article DESC
	LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

// calcul du total grâce à SQL_CALC_FOUND_ROWS
$totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
$total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));
$nbrPages = ceil($total / $limite);

while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // info de la sortie liée && de la commission liée à la sortie liée (simple ou pas ?)
    if ($handle['evt_article'] > 0) {
        $req = 'SELECT
				code_evt, id_evt, titre_evt
				, code_commission
			FROM caf_evt, caf_commission
			WHERE id_evt = ' . (int) $handle['evt_article'] . '
			AND id_commission = commission_evt
			LIMIT 1';
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $handle['evt'] = $handle2;
        }
    }

    $articlesTab[] = $handle;
}
?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">

		<!-- Slider -->
		<div id="home-slider">
			<!--
			<div id="home-slider-helper">
				SOUS CETTE PHOTO<br />SE CACHE UN ARTICLE<br />CLIQUEZ POUR LE VOIR !
			</div>
			// -->
			<!-- nav-spots -->
			<div id="home-slider-nav">
				<div id="home-slider-nav-wrapper">
					<div style="width:0px">
						<img src="/img/home-slider-nav-1.png" alt="" title="" />
						<?php
                        for ($i = 0; $i < count($sliderTab); ++$i) {
                            echo '<a href="javascript:void(0)" title="" class="' . ($i ? '' : 'up') . '"><span>' . ($i + 1) . '</span></a>';
                        }
?>
						<!--
						<a href="javascript:void(0)" title="" class="up"><span>1</span></a>
						<a href="javascript:void(0)" title=""><span>2</span></a>
						<a href="javascript:void(0)" title=""><span>3</span></a>
						<a href="javascript:void(0)" title=""><span>4</span></a>
						-->
						<img src="/img/home-slider-nav-2.png" alt="" title="" />
					</div>
				</div>
			</div>
			<!-- nav-fleches -->
			<div id="home-slider-nav2">
				<a href="javascript:void(0)" title="" class="arrow-left"><img src="/img/arrow-left.png" alt="&lt;" title="" /></a>
				<a href="javascript:void(0)" title="" class="arrow-right"><img src="/img/arrow-right.png" alt="&gt;" title="" /></a>
			</div>
			<!-- slides -->
			<div id="home-slider-wrapper">

				<?php
                for ($i = 0; $i < count($sliderTab); ++$i) {
                    $article = $sliderTab[$i];

                    // check image
                    $img = '';
                    if ($article['media_upload_id']) {
                        $img = LegacyContainer::get('legacy_twig')->getExtension('App\Twig\MediaExtension')->getLegacyThumbnail(['filename' => $article['filename']], 'wide_thumbnail');
                    }

                    echo '<a href="' . LegacyContainer::get('legacy_router')->generate('article_view', ['code' => html_utf8($article['code_article']), 'id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL) . '" class="slide" style="background-image:url(' . $img . ')" title="CLIQUEZ POUR VOIR L\'ARTICLE">
                        <div class="bandeau-slider">
                            <p class="alaune">ARTICLE A LA UNE</p>
                            <h2>' . html_utf8($article['titre_article']) . '</h2>
						</div>
					</a>';
                }
?>

			</div>
		</div>

		<div style="padding:10px 0 20px 30px" id="home-actus">
			<?php
            // H1 : TITRE PRINCIPAL DE LA PAGE EN FONCTION DE LA COMM COURANTE
            // nom par défaut (hors commission selectionnéé) -->
            if (!$current_commission) {
                echo '<h1 class="actus-h1" id="home-articles">Les actus du <b>' . $p_sitename . '</b></h1>';
            }
            // nom en fonction de la commission
            else {
                echo '<h1 class="actus-h1 shortened" id="home-articles">Les actus <b>' . html_utf8($comTab[$current_commission]['title_commission']) . '</b></h1>';
                if (count($articlesTab)) {
                    echo '<a href="/accueil.html" title="Afficher tous les articles sans distinction" class="lien-big" style="float:right; margin:6px 20px 0 0"><span style="color:#3C91BF">&gt;</span> Voir toutes les actus</a>';
                }
            }

// LISTE D'ARTICLES
if (!count($articlesTab)) {
    echo '<p><br />Aucune actualité pour cette commission...</p>';
}
for ($i = 0; $i < count($articlesTab); ++$i) {
    $article = $articlesTab[$i];
    require __DIR__ . '/../includes/article-lien.php';
}

// paginatino
if ($nbrPages > 1) {
    echo '<nav class="pageSelect"><br />';
    // navigation droite/gauche
    echo '<div class="navleftright"><a href="javascript:void(0)" title="" class="arrow left">&lt;&lt;</a><a href="javascript:void(0)" title="" class="arrow right">&gt;&gt;</a></div>';

    echo '<div class="pageSelectIn"><div class="pageSelectInWrapper">';
    for ($i = 1; $i <= $nbrPages; ++$i) {
        // if($i>1 && $i%10 == 1) echo '<br />';
        echo '<a href="' . $versCettePage . '?pagenum=' . $i . '#home-actus" title="" class="' . ($pagenum == $i ? 'up' : '') . '">P' . $i . '</a> ' . ($i < $nbrPages ? '  ' : '');
    }
    echo '</div></div>';
    echo '</nav>';
}

echo '<br style="clear:both" />';
if ($current_commission) {
    echo '<a href="/accueil.html#home-actus" title="Afficher tous les articles sans distinction" class="lien-big" style="float:right; margin:6px 20px 0 0"><span style="color:#3C91BF">&gt;</span> Voir toutes les actus</a>';
}

?>
			<!-- liens vers les flux RSS -->
			<a href="/rss.xml?mode=articles" title="Flux RSS de toutes les actualités du club" class="nice2">
				<img src="/img/base/rss.png" alt="RSS" title="" /> &nbsp;
				actualités du club
			</a>
			<?php
if ($current_commission) {
    echo '<a href="/rss.xml?mode=articles-' . $current_commission . '" title="Flux RSS des actualités «' . $current_commission . '» uniquement" class="nice2">
						<img src="/img/base/rss.png" alt="RSS" title="" /> &nbsp;
						actualités «' . $comTab[$current_commission]['title_commission'] . '»
					</a>';
}
?>
			<br style="clear:both" />

		</div>

	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>


	<br style="clear:both" />
</div>