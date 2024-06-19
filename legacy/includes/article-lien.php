<?php
// URL
$url = 'article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html';
// if($current_commission) $url.='?commission='.$current_commission; // v1 : commission courante sur la page
if ($article['commission']['code_commission'] ?? null) {
    $url .= '?commission=' . $article['commission']['code_commission'];
} // V2 commission de cet article
elseif ($article['evt']['code_commission'] ?? null) {
    $url .= '?commission=' . $article['evt']['code_commission'];
} // commission de la sortie associée

// disabling links :
if (1 != $article['status_article']) {
    if (!allowed('article_edit', 'commission:' . $article['commission_article'])) {
        $url = "javascript:$.fancybox('<p class=\'info\'>Désolé, vous ne pouvez pas ouvrir cette page <br />car cet article n\'est pas publié sur le site.</p>')";
    }
}

// check image
if (is_file(__DIR__ . '/../../public/ftp/articles/' . (int) $article['id_article'] . '/min-figure.jpg')) {
    $img = '/ftp/articles/' . (int) $article['id_article'] . '/min-figure.jpg';
} else {
    $img = '/ftp/articles/0/min-figure.jpg';
}

?>
<div class="encart_article">
	<!-- image -->

	<a title="Voir cet article" href="<?php echo $url; ?>" class="illustration fader" style="background-image:url('<?php echo $img; ?>')"></a>
	<!-- titre + lien article -->
	<h2>
		<a href="<?php echo $url; ?>" title="Voir cet article">
			<?php echo html_utf8($article['titre_article']); ?>
		</a>
	</h2>

	<!-- lien commission -->
	<p class="commission-title">

		<?php

        echo date('d.m.y - ', $article['tsp_article']);

// une commission est bien liée
if ($article['commission'] ?? null) {
    ?>
			<a href="/accueil/<?php echo html_utf8($article['commission']['code_commission']); ?>.html#home-articles" title="Toutes les actus de cette commission">
				<?php echo html_utf8($article['commission']['title_commission']); ?>
			</a>
			<?php
}
// 0 = actu club
elseif (0 == $article['commission_article']) {
    ?>
			<a href="/accueil.html#home-articles" title="Toutes les actus du club">
				CLUB
			</a>
			<?php
}
// -1 = compte rendu de sortie (code_commission compris dans evt)
elseif (-1 == $article['commission_article']) {
    $urlEvt = 'sortie/' . $article['evt']['code_evt'] . '-' . $article['evt']['id_evt'] . '.html?commission=' . html_utf8($article['evt']['code_commission']); ?>
			<a href="<?php echo $urlEvt; ?>" title="Voir la sortie liée à cet article : &laquo; <?php echo html_utf8($article['evt']['titre_evt']); ?> &raquo;">
				compte rendu de sortie
			</a>
			<?php
}

?>

	</p>
	<!-- summup -->
	<p class="summup">
		<?php echo limiterTexte(strip_tags($article['cont_article']), 170); ?>
		<a href="<?php echo $url; ?>" title="Voir cet article">
			[...]
		</a>
	</p>
	<br style="clear:both" />
</div>