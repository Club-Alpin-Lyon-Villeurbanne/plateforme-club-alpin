<?php
// URL

use App\Legacy\LegacyContainer;

$url = 'article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html';
if (isset($current_commission) && $current_commission) {
    $url .= '?commission=' . $current_commission;
}

// check image
$img = '';
if ($article['media_upload_id']) {
    $img = LegacyContainer::get('legacy_twig')->getExtension('App\Twig\MediaExtension')->getLegacyThumbnail(['filename' => $article['filename']], 'min_thumbnail');
}

?>

<div class="encart_article_small">
	<!-- image -->
	<a target="_top" title="Voir cet article" href="<?php echo $url; ?>" class="illustration fader" style="background-image:url('<?php echo $img; ?>')"></a>

	<!-- titre + lien article -->
	<h2>
		<a target="_top" href="<?php echo $url; ?>" title="Voir cet article">
			<?php echo html_utf8($article['titre_article']); ?>
		</a>
	</h2>
	<!-- summup -->
	<p class="summup">
		<?php echo limiterTexte(strip_tags($article['cont_article']), 170); ?>
		<a target="_top" href="<?php echo $url; ?>" title="Voir cet article">
			[...]
		</a>
	</p>
	<br style="clear:both" />
</div>