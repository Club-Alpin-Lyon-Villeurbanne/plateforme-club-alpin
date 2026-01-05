<?php
// URL

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Helper\HtmlHelper;

$url = LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $article['code_article'], 'id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL);
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
			<?php echo HtmlHelper::escape($article['titre_article']); ?>
		</a>
	</h2>

	<!-- lien commission -->
	<p class="commission-title">

		<?php
        if (!empty($article['validation_date'])) {
            $creationDate = new \DateTime($article['validation_date']);
            echo $creationDate->format('d/m/Y') . ' - ';
        }

// une commission est bien liée
if ($article['code_commission'] ?? null) {
    ?>
			<a href="/accueil/<?php echo rawurlencode($article['code_commission']); ?>.html#home-articles" title="Toutes les actus de cette commission">
				<?php echo HtmlHelper::escape($article['title_commission']); ?>
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
    $urlEvt = LegacyContainer::get('legacy_router')->generate('sortie', ['code' => $article['evt']['code_evt'], 'id' => (int) $article['evt']['id_evt']], UrlGeneratorInterface::ABSOLUTE_URL) . '?commission=' . urlencode($article['evt']['code_commission']); ?>
			<a href="<?php echo $urlEvt; ?>" title="Voir la sortie liée à cet article : &laquo; <?php echo HtmlHelper::escape($article['evt']['titre_evt']); ?> &raquo;">
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