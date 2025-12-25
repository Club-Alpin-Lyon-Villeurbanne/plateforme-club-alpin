<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Helper\HtmlHelper;

?>
<div class="sharebox">
	<div>
			<a target="_blank" title="Twitter" href="https://twitter.com/share?url=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . $versCettePage); ?>&text=<?php echo HtmlHelper::escape($article['titre_article']); ?>&via=Club Alpin Français" rel="nofollow" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;"><img width="30" height="30" src="/img/social/twitter.png" alt="Twitter" /></a>
			<a target="_blank" title="Facebook" href="https://www.facebook.com/sharer.php?u=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . $versCettePage); ?>&t=<?php echo HtmlHelper::escape($article['titre_article']); ?>" rel="nofollow" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=500,width=700');return false;"><img width="30" height="30" src="/img/social/facebook.png" alt="Facebook" /></a>
			<a target="_blank" title="Envoyer par mail" href="mailto:?subject=Article:<?php echo HtmlHelper::escape($article['titre_article']); ?>&body=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . $versCettePage); ?>" rel="nofollow"><img width="30" height="30" src="/img/social/email-blue.png" alt="email" /></a>
		</div>
</div>
