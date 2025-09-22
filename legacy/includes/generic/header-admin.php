<?php
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

?>
	<!-- css -->
    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles'); ?>
    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('common-styles'); ?>
    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('admin-styles'); ?>
	<link rel="stylesheet" href="/css/ui-cupertino/jquery-ui-1.8.18.custom.css" type="text/css"  media="screen" />
	<link rel="stylesheet" href="/tools/fancybox/jquery.fancybox.css" type="text/css" media="screen" />

    <!-- jquery -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="/js/jquery-1.12.4.min.js">\x3C/script>')</script>
	<script type="text/javascript" src="/js/jquery-ui-1.8.16.full.min.js"></script>
	<script type="text/javascript" src="/js/jquery.easing.1.3.js"></script>
	<script type="text/javascript" src="/js/jquery.color.js"></script>
	<!-- fancybox -->
    <script type="text/javascript" src="/tools/fancybox/jquery.fancybox.pack.js"></script>
	<!-- datatables -->
    <script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>
	<link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.css" type="text/css" media="screen" />

	<!-- script persos -->
    <script src="/js/fonctions.js" type="text/javascript"></script>
	<?php
    if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) { ?>
		<!-- script admin -->
		<script src="/js/fonctionsAdmin.js" type="text/javascript"></script>
	<?php } ?>
	<!-- on ready -->
    <script src="/js/onready.js" type="text/javascript"></script>