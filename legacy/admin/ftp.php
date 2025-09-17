<?php

use App\Ftp\FtpFile;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

global $dossier;

require __DIR__ . '/../app/includes.php';

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
    exit;
}

// Selection des fichiers a afficher ou pas
// NOW IN PARAMS.PHP

$mode = isset($_GET['mode']) || array_key_exists('mode', $_GET) ? $_GET['mode'] : 'relatif';

// recuperation du dossier

$ftpPath = LegacyContainer::getParameter('legacy_ftp_path');
if (!isset($_GET['dossier'])) {
    $dossier = '';
} else {
    $dossier = $_GET['dossier'];
}

// checks :
// if (substr($dossier, 0, strlen($ftpPath)) != $ftpPath || mb_substr_count($dossier, '../') > 2) {
//    echo '<p class="erreur">Erreur ! Le dossier demandé n\'a pas le bon format.</p>';
//    exit;
// }

?><!doctype html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>DOSSIER FTP</title>

        <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('admin-styles'); ?>
        <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles'); ?>
		<link rel="stylesheet" href="/tools/fancybox/jquery.fancybox.css" type="text/css" media="screen" />

		<!-- jquery -->
		<script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="/js/fonctionsAdmin.js"></script>
		<script type="text/javascript" src="/admin/ftp.js"></script>
		<!-- fancybox -->
		<script type="text/javascript" src="/tools/fancybox/jquery.fancybox.pack.js" charset="utf-8"></script>
		<!-- <script type="text/javascript" src="/tools/fancybox/jquery.mousewheel-3.0.4.pack.js" charset="utf-8"></script> -->
		<!-- datatables -->
		<script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js" charset="utf-8"></script>
		<link href="/css/datatables-ftp.css" rel="stylesheet" type="text/css">



	</head>
	<!--  if(parent.adjustIFrameSize)parent.adjustIFrameSize(window); -->
	<body style="background:#DEDEDE; border:none;">
		<div id="ftp-2">

			<!-- A gauche : l'arborescence -->
			<div id="ftp-2-arbo">

				<!-- création de dossier -->
				<div class="ftp-dir-add">
					<img src="/img/base/folder_add.png" alt="" title="" /> Créer un nouveau dossier ici
				</div>
				<br />


				<div class="level top">
					<a href="" title="Racine du dossier" class="dirlink <?php if ('' == $dossier) {
					    echo 'selected';
					}?>">Dossier FTP</a>
					<?php
					                    function arbo_read($ftpPath, $dir, $level)
					                    {
					                        global $dossier;
					                        $one = false; // booleen : un dossier trouve au moins
					                        $opendir = opendir($ftpPath . $dir);
					                        $files = [];

					                        $j = 0; // compte des fichiers
					                        while ($f = readdir($opendir)) {
					                            $files[$j] = $f;
					                            ++$j;
					                        }
					                        sort($files);

					                        foreach ($files as $file) {
					                            // c'est un dossier, non masqué
					                            if (is_dir($ftpPath . $dir . $file) && !FtpFile::shouldHide($file)) {
					                                $one = true;
					                                echo '<div class="level level' . $level . '">'
					                                    . '<a class="dirtrigger" href="' . $file . '/" title=""></a>'
					                                    . '<a class="dirlink ' . ($dossier == $dir . $file . '/' ? 'selected' : '') . '" href="' . $dir . $file . '/" title="">' . $file . '</a>';
					                                // if(!arbo_read($dir.$file.'/', $level+1)) echo '<div class="level level'.($level+1).'">-</div>';
					                                if (!arbo_read($ftpPath, $dir . $file . '/', $level + 1)) {
					                                    echo '<span class="removetrigger"></span>';
					                                }
					                                echo '</div>';
					                            }
					                        }

					                        return $one;
					                    }
arbo_read($ftpPath, '', 0);
?>
				</div>
			</div>


			<!-- A droite : les contenus -->
			<div id="ftp-2-droite">

				<!-- valums file upload -->
				<link href="/css/ftp-fileuploader.css" rel="stylesheet" type="text/css">
				<script src="/tools/valums-file-upload/js/fileuploader.js" type="text/javascript"></script>
				<script>
					function createUploader(){
						var uploader = new qq.FileUploader({
							sizeLimit: 100 * 1024 * 1024, // 100 Megz
							element: document.getElementById('file-uploader-ftp'),
							action: '/valums-file-upload/server/ftp.php?dossier='+encodeURIComponent(currentDir),
							// pour chaque image envoyée
							onComplete: function(id, fileName, responseJSON){
								if(responseJSON.success){
									// suppression du message d'envoi et actualisation du tableau
									$("li.qq-upload-success").hide();
									updateRight(currentDir);
								}
								/**
								// else console.log(responseJSON);
								// rechargement du cadre
								if(!$(".qq-upload-spinner:visible").length && !$(".qq-upload-fail:visible").length)
									window.location.href='ftp.php?mode=<?php echo $mode; ?>&dossier=<?php echo $dossier; ?>';
								// message d'erreur si certains ont fonctionné
								if($(".qq-upload-fail:visible").length && $(".qq-upload-success:visible").length){
									html='<p class="erreur">Certains éléments ont bien été envoyés, mais pas tous. <a href="/ftp.php?mode=<?php echo $mode; ?>&dossier=<?php echo $dossier; ?>" >Cliquez ici pour actualiser le cadre et afficher les nouveaux éléments</a>.</p>';
									$("#uploadLog").html(html);
								}
								*/
							},
							debug: true
						});
						// style du bouton
						$('.qq-uploader .qq-upload-button').prepend('<img src="/img/base/arrow_down.png" alt="" title="" /> ');
					}
					window.onload = createUploader;
				</script>

				<div id="file-uploader-ftp" style="min-height:20px;"><noscript>L'envoi de fichier nécessite javascript</noscript></div>
				<div id="uploadLog"></div>
				<!-- // valums file upload -->


				<br />
				<!-- fil d'ariane généré par js -->
				<div id="ftp-ariane"></div>
				<!-- tableau généré par ajax + datatables -->
				<table id="ftp-2-fichiers" style="width:680px;">
					<thead>
						<tr>
							<th>Ico</th>
							<th>Nom</th>
							<th>Poids</th>
							<th>Modifié le</th>
							<th>Type</th>
							<th>Dimensions</th>
							<th>Outils</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>


			</div>

			<br style="clear:both" />
			<br />
			<br />
			<p style="color:gray; font-size:0.7rem; line-height:11px; border-top:1px solid silver; padding-top:5px;">
				Aide : Le dossier FTP est le disque dur de votre site internet.
				Vous pouvez y déposer des fichiers pour les proposer en téléchargement, ou des images à intégrer dans les pages du site.<br />
				A gauche, la navigation rapide vous permet de vous déplacer avec fluidité dans les dossiers et sous-dossiers.<br />
				A droite, vous voyez un bouton pour ajouter un dossier au niveau courant, ou bien y déposer des fichiers.<br />
			</p>

		</div>

		<!-- Utils -->
		<a href="" id="freeFancyFrame"></a>

		<!-- Waiters -->
		<div id="loading" style="display:none;position:fixed; top:0px; left:0px; z-index:800; height:100%; width:100%; background:rgba(150,150,150,0.5); text-align:center; line-height:100%;">
			<img src="/img/base/loading.gif" alt="" title="" style="vertical-align:middle; background:rgba(255,255,255,1); border:1px solid silver; border-radius:10px; padding:10px; margin-top:40px; box-shadow:0 0 200px -7px black;" />
		</div>


		<script type="text/javascript">
		window.parent.actu_iframe();
		</script>
	</body>
	</html>
	<?php
