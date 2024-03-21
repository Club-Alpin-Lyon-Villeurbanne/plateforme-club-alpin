<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

require __DIR__.'/../app/includes.php';

if (!admin()) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Votre session administrateur a expiré';
    exit;
}

$targetRel = $_GET['target'];
$targetAbs = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL)
    .(str_starts_with($targetRel, '/') ? substr($targetRel, 1) : $targetRel); // substr = supprimer admin/

?><!doctype html>
	<html lang="fr">
		<head>
			<meta charset="utf-8">
			<title>DOSSIER FTP</title>

	        <link rel="stylesheet" href="/build/tailwind.css" type="text/css"  media="screen" />
			<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/build/legacy-admin.css">
			<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/build/legacy-base.css">
			<!-- jquery -->
			<script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>

		</head>
		<body class="ftp-frame">

			<h3>URL absolue</h3>
			<p>Pour partager un fichier par e-mail, ou sur un autre site internet.</p>
            <div class="input-wrapper">
                <input type="text" id="absolute-url" name="" value="<?php echo html_utf8($targetAbs); ?>" class="urlSelecter" />
                <input type="button" class="copy-paste nice" data-target="absolute-url" value="Copier" />
            </div>
			<br />
			<br />

			<h3>URL relative</h3>
			<p>Pour affichage d'une image, ou pour créer un lien vers un fichier, dans une page de ce site.</p>
            <div class="input-wrapper">
                <input type="text" id="relative-url" name="" value="<?php echo html_utf8($targetRel); ?>" class="urlSelecter" />
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
