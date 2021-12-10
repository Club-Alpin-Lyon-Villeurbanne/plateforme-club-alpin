<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

?>
<div class="sharebox">
	<!-- Place this tag in your head or just before your close body tag. -->
	<script type="text/javascript" src="https://apis.google.com/js/plusone.js">
	  {lang: 'fr'}
	</script>

	<div style="float:right">
			<!-- Place this tag where you want the +1 button to render. -->
			<div class="g-plusone"></div>
	</div>
	<div>
			<a target="_blank" title="Twitter" href="https://twitter.com/share?url=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).$versCettePage); ?>&text=<?php echo html_utf8($article['titre_article']); ?>&via=Club Alpin Français" rel="nofollow" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=700');return false;"><img width="30" height="30" src="/img/social/twitter.png" alt="Twitter" /></a>
			<a target="_blank" title="Facebook" href="https://www.facebook.com/sharer.php?u=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).$versCettePage); ?>&t=<?php echo html_utf8($article['titre_article']); ?>" rel="nofollow" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=500,width=700');return false;"><img width="30" height="30" src="/img/social/facebook.png" alt="Facebook" /></a>
			<a target="_blank" title="Google +" href="https://plus.google.com/share?url=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).$versCettePage); ?>&hl=fr" rel="nofollow" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=450,width=650');return false;"><img width="30" height="30" src="/img/social/googleplus.png" alt="Google Plus" /></a>
			<a target="_blank" title="Envoyer par mail" href="mailto:?subject=Article:<?php echo html_utf8($article['titre_article']); ?>&body=<?php echo urlencode(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).$versCettePage); ?>" rel="nofollow"><img width="30" height="30" src="/img/social/email-blue.png" alt="email" /></a>
		</div>
</div>
