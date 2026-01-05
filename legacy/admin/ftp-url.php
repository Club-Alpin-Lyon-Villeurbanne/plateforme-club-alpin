<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

require __DIR__ . '/../app/includes.php';

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
    exit;
}

$targetRel = $_GET['target'];
$targetAbs = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL)
    . (str_starts_with($targetRel, '/') ? substr($targetRel, 1) : $targetRel); // substr = supprimer admin/

?><!doctype html>
	<html lang="fr">
		<head>
			<meta charset="utf-8">
			<title>DOSSIER FTP</title>

            <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('admin-styles'); ?>
            <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles'); ?>
			<!-- jquery -->
			<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
			<script>window.jQuery || document.write('<script src="/js/jquery-1.12.4.min.js">\x3C/script>')</script>

		</head>
		<body class="ftp-frame">

			<h3>URL absolue</h3>
			<p>Pour partager un fichier par e-mail, ou sur un autre site internet.</p>
            <div class="input-wrapper">
                <input type="text" id="absolute-url" name="" value="<?php echo HtmlHelper::escape($targetAbs); ?>" class="urlSelecter" />
                <input type="button" class="copy-paste nice" data-target="absolute-url" value="Copier" />
            </div>
			<br />
			<br />

			<h3>URL relative</h3>
			<p>Pour affichage d'une image, ou pour créer un lien vers un fichier, dans une page de ce site.</p>
            <div class="input-wrapper">
                <input type="text" id="relative-url" name="" value="<?php echo HtmlHelper::escape($targetRel); ?>" class="urlSelecter" />
                <input type="button" class="copy-paste nice" data-target="relative-url" value="Copier" />
            </div>

			<script type="text/javascript">
				$('.urlSelecter').bind('focus click', function(){
					$(this).select();
					resetInputs();
				});
				$('.urlSelecter').bind('mouseup', function(){
					return false;
				});
                const initInputs = function () {
                    document.querySelectorAll('.copy-paste').forEach(function (input) {
                        input.addEventListener('click', function () {
                            const target = document.getElementById(this.dataset.target);
                            target.select();
                            target.setSelectionRange(0, 99999);
                            navigator.clipboard.writeText(target.value);
                            this.value = 'Copié ✓';
                        });
                    });
                };
                const resetInputs = function () {
                    document.querySelectorAll('.copy-paste').forEach(function (input) {
                        input.value = 'Copier';
                    });
                };
                resetInputs();
                initInputs();
			</script>

		</body>
	</html>
	<?php

?>
