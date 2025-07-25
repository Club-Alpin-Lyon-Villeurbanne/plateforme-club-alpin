<?php
// URL

use App\Legacy\LegacyContainer;

$url = 'article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html';
if ($article['code_commission'] ?? null) {
    $url .= '?commission=' . $article['code_commission'];
} // commission de la sortie associée

// disabling links :
if (1 != $article['status_article']) {
    if (!allowed('article_edit', 'commission:' . $article['commission_article'])) {
        $url = "javascript:$.fancybox('<p class=\'info\'>Désolé, vous ne pouvez pas ouvrir cette page <br />car cet article n\'est pas publié sur le site.</p>')";
    }
}

$img = '';
if ($article['media_upload_id']) {
    $img = LegacyContainer::get('legacy_twig')->getExtension('App\Twig\MediaExtension')->getLegacyThumbnail(['filename' => $article['filename']], 'min_thumbnail');
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
if ($article['code_commission'] ?? null) {
    ?>
			<a href="/accueil/<?php echo html_utf8($article['code_commission']); ?>.html#home-articles" title="Toutes les actus de cette commission">
				<?php echo html_utf8($article['title_commission']); ?>
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