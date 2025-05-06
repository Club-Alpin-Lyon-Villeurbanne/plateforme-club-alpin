<?php

use App\Legacy\LegacyContainer;
use App\Twig\JwtExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        if ('feuille-de-sortie' == $p1) {
            ?>
		<style type="text/css">
		<?php
                    include __DIR__ . '/../../../public/css/style1.css';
            include __DIR__ . '/../../../public/fonts/stylesheet.css';
            include __DIR__ . '/../../../public/css/base.css';
            include __DIR__ . '/../../../public/css/common.css';
            include __DIR__ . '/../../../public/css/print.css';
            ?>
		</style>
		<?php
        } else {
            ?>
		<link rel="stylesheet" href="/css/style1.css" type="text/css" />
		<link rel="stylesheet" href="/fonts/stylesheet.css" type="text/css" />
		<link rel="stylesheet" href="/css/base.css" type="text/css"  />
		<link rel="stylesheet" href="/css/common.css?v=<?php echo mt_rand(); ?>" type="text/css"  />
		<link rel="stylesheet" href="/tools/fancybox/jquery.fancybox.css" type="text/css" />
		<!-- css PRINTS -->
		<link rel="stylesheet" href="/css/print.css" type="text/css"  media="print" />
	<?php
        }
?>


    <!-- html5shiv -->
	<script type="text/javascript" src="/js/html5shiv.js"></script>
    <!-- jquery -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="/js/jquery-1.8.min.js">\x3C/script>')</script>
	<script type="text/javascript" src="/js/jquery.easing.1.3.js"></script>
	<script type="text/javascript" src="/js/jquery.pngFix.pack.js"></script>
	<script type="text/javascript" src="/js/jquery.color.js"></script>
	<!-- au besoin
	<script type="text/javascript" src="/js/jquery.animate-shadow-min.js"></script>
	<script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>
	<script type="text/javascript" src="/js/jquery.backgroundPosition.js"></script>
	-->
	<!-- fancybox -->
    <script type="text/javascript" src="/tools/fancybox/jquery.fancybox.pack.js"></script>
	<!-- <script type="text/javascript" src="/tools/fancybox/jquery.mousewheel-3.0.4.pack.js"></script> -->

	<!-- script persos -->
    <script src="/js/fonctions.js" type="text/javascript"></script>
	<!-- script scroll up -->
    <script src="/js/scrollup.js" type="text/javascript"></script>

	<?php
    use App\Security\SecurityConstants;

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) { ?>
		<!-- script admin -->
		<script src="/js/fonctionsAdmin.js" type="text/javascript"></script>
	<?php } ?>

    <script src="/js/onready.js" type="text/javascript"></script>
    <script src="/js/onready-site.js" type="text/javascript"></script>


	<!-- OPENGRAPHS -->
	<meta property="og:title" content="<?php echo html_utf8($meta_title); ?>" />
	<meta property="og:description" content="<?php echo htmlspecialchars_decode(html_utf8($meta_description)); ?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="<?php echo $versCettePage; ?>" />
	<?php if ($ogImage) { ?>
		<meta property="og:image" content="<?php echo $ogImage; ?>" />
	<?php } ?>
	<meta property="og:site_name" content="<?php echo html_utf8($p_sitename); ?>" />

	<!-- RSS -->
	<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>rss.xml?mode=articles" />
