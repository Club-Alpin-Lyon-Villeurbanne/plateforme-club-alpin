<?php
// URL

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Helper\HtmlHelper;

$url = LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $article['code_article'], 'id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL);
if (isset($current_commission) && $current_commission) {
    $url .= '?commission=' . urlencode($current_commission);
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
			<?php echo HtmlHelper::escape($article['titre_article']); ?>
		</a>
	</h2>
	<!-- summup -->
	<p class="summup">
		<?php echo limiterTexte(strip_tags($article['cont_article']), 150); ?>
		<a target="_top" href="<?php echo $url; ?>" title="Voir cet article">
			[...]
		</a>
	</p>
	<br style="clear:both" />
</div>