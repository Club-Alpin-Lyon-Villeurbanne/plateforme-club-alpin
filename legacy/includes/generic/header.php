<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use App\Twig\JwtExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Helper\HtmlHelper;

global $ogImage;
$jwt = LegacyContainer::get(JwtExtension::class)->generateJwtToken();
$p_sitename = LegacyContainer::getParameter('legacy_env_SITENAME');
?>
	<!-- vars php passées au js -->
    <script type="text/javascript">
	var lang='fr';
	var p1='<?php echo $p1; ?>';
	var p2='<?php echo $p2; ?>';
	var p3='<?php echo $p3; ?>';
	var p4='<?php echo $p4; ?>';
	</script>

	<!-- icon -->
	<link rel="shortcut icon" href="/favicon.ico" />
<script>
	localStorage.setItem('jwt', "<?php echo $jwt; ?>")
</script>

	<!-- css SCREEN ONLY  -->
	<!-- media="screen" -->
	<!-- css COMMUNS SCREEN + PRINT -->
	 <?php
            echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('styles');
echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('fonts');
echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles');
echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('common-styles');
?>
		<link rel="stylesheet" href="/tools/fancybox/jquery.fancybox.css" type="text/css" />
		<!-- css PRINTS -->
        <?php
echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('print-styles', ['attr' => ['media' => 'print']]);
echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('modal_css');
?>

    <!-- jquery -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="/js/jquery-1.12.4.min.js">\x3C/script>')</script>
	<script type="text/javascript" src="/js/jquery.easing.1.3.js"></script>
	<script type="text/javascript" src="/js/jquery.color.js"></script>
	<!-- fancybox -->
    <script type="text/javascript" src="/tools/fancybox/jquery.fancybox.pack.js"></script>
	<!-- <script type="text/javascript" src="/tools/fancybox/jquery.mousewheel-3.0.4.pack.js"></script> -->

	<!-- script persos -->
    <script src="/js/fonctions.js" type="text/javascript"></script>
	<!-- script scroll up -->
    <script src="/js/scrollup.js" type="text/javascript"></script>
	<?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteScriptTags('modal'); ?>


	<?php

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) { ?>
		<!-- script admin -->
		<script src="/js/fonctionsAdmin.js" type="text/javascript"></script>
	<?php } ?>

    <script src="/js/onready.js" type="text/javascript"></script>
    <script src="/js/onready-site.js" type="text/javascript"></script>


	<!-- OPENGRAPHS -->
	<meta property="og:title" content="<?php echo HtmlHelper::escape($meta_title); ?>" />
	<meta property="og:description" content="<?php echo htmlspecialchars_decode(HtmlHelper::escape($meta_description)); ?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="<?php echo $versCettePage; ?>" />
	<?php if ($ogImage) { ?>
		<meta property="og:image" content="<?php echo $ogImage; ?>" />
	<?php } ?>
	<meta property="og:site_name" content="<?php echo HtmlHelper::escape($p_sitename); ?>" />

	<!-- RSS -->
	<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>rss.xml?mode=articles" />
