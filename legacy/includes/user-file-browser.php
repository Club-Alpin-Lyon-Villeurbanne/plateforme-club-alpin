<?php

include __DIR__.'/../app/includes.php';

if (user()) {
    // bien connecté ?
    $id_user = (int) ($_SESSION['user']['id_user']);
    if (!$id_user) {
        echo 'ERREUR : id invalide';
        exit();
    }

    // première visite : dossier inexistant
    if (!file_exists(__DIR__.'/../../public/ftp/user/'.$id_user)) {
        if (!mkdir($concurrentDirectory = __DIR__.'/../../public/ftp/user/'.$id_user) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }
    if (!file_exists(__DIR__.'/../../public/ftp/user/'.$id_user.'/images/')) {
        if (!mkdir($concurrentDirectory = __DIR__.'/../../public/ftp/user/'.$id_user.'/images/') && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }
    if (!file_exists(__DIR__.'/../../public/ftp/user/'.$id_user.'/files/')) {
        if (!mkdir($concurrentDirectory = __DIR__.'/../../public/ftp/user/'.$id_user.'/files/') && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    // recuperation du dossier
    $type = $_GET['type'];
    if ('image' == $type) {
        $dossier = __DIR__.'/../../public/ftp/user/'.$id_user.'/images/';
    } elseif ('file' == $type) {
        $dossier = __DIR__.'/../../public/ftp/user/'.$id_user.'/files/';
    } else {
        echo "ERREUR : type invalide ($type)";
        exit();
    } ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="https://www.w3.org/1999/xhtml" xml:lang="fr">
		<head>
		<title><?php if ('image' == $type) {
        echo 'Vos images en ligne';
    } else {
        echo 'Vos fichiers en ligne';
    } ?></title>

		<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/css/style1.css">
		<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/css/base.css">

		<!-- valums file upload -->
		<link href="/tools/valums-file-upload/css/fileuploader-user.css" rel="stylesheet" type="text/css">

		<!-- commnuicate with window arbo-->
		<script type="text/javascript" src="/tools/tinymce/tiny_mce_popup.js"></script>

		<!-- jquery
		<script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>
		-->
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
		<script type="text/javascript" src="/js/fonctionsAdmin.js"></script>

		<!-- fancybox -->
		<link rel="stylesheet" href="/tools/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
		<script type="text/javascript" src="/tools/fancybox/jquery.fancybox-1.3.4.pack.js" charset="utf-8"></script>
		<script type="text/javascript" src="/tools/fancybox/jquery.mousewheel-3.0.4.pack.js" charset="utf-8"></script>

		<!-- Datatables -->
		<link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.sobre.css" type="text/css" media="screen" />
		<script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>


		<script type="text/javascript">
		// jquery
		$(document).ready(function(){

			// fancybox
			$("a.fancybox").fancybox();

			// datatables
			$('table').dataTable( {
				"aaSorting": [[ 4, "desc" ]],
				"bLengthChange": false
				// "sDom": 'T<"clear">lfrtip'
			} );

			// outils : delete
			$('.file-delete').click(function(){
				var tmp=$(this).parents('tr:first').find('a:first').attr('href').split('/');
				var file=tmp[tmp.length-1];
				if(confirm("Voulez-vous vraiment supprimer le fichier :\n"+file+" \n\nCelui-ci ne sera plus disponible et renverra une erreur 404 si vous l'avez intégré dans une page web."))
					window.location = 'user-file-browser.php?type=<?php echo $type; ?>&operation=delete&file='+file;
			});


		});

		// tinyMCE
		var FileBrowserDialogue = {
			init : function () {
				// Here goes your code for setting your custom things onLoad.
			},
			mySubmit : function (URL) {
				// var URL = document.my_form.my_field.value;
				var win = tinyMCEPopup.getWindowArg("window");

				// insert information now
				win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

				// are we an image browser
				if (typeof(win.ImageDialog) != "undefined") {
					// we are, so update image dimensions...
					if (win.ImageDialog.getImageData)
						win.ImageDialog.getImageData();

					// ... and preview if necessary
					if (win.ImageDialog.showPreviewImage)
						win.ImageDialog.showPreviewImage(URL);
				}

				// close popup window
				tinyMCEPopup.close();
			}
		}
		tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);

		function inserer(monUrl){
			FileBrowserDialogue.mySubmit(monUrl);
		}
		</script>
	</head>
	<body style="background:#f0f0ee; border:none; margin:0px; padding:0px; ">
		<div id="lpFileBrowser" style="padding:5px;">

			<!-- TEXTE EXPLICATIF -->
			<p>
				<?php
                if ('image' == $type) {
                    echo '
					Déposez ici les images que vous souhaitez insérer dans vos articles. Elles seront redimensionnées automatiquement
					pour correspondre au format du site. Seules les images .jpg et .png sont autorisées, poids maximum : 5Mo.
					';
                }
    if ('file' == $type) {
        echo "
					Déposez ici les fichiers que vous souhaitez proposer en téléchargement. Poid maximum : 5Mo.<br />
					Ext. autorisées : <span style='font-size:9px'>".implode(', ', $p_ftpallowed).'</span>
					';
    } ?>
			</p>

			<!-- valums file upload -->
			<div id="file-uploader-ftp"><noscript>L'envoi de fichier nécessite javascript</noscript></div>
			<script src="/tools/valums-file-upload/js/fileuploader.js" type="text/javascript"></script>
			<script>
				function createUploader(){
					var uploader = new qq.FileUploader({

						sizeLimit: 5 * 1024 * 1024, // 5 Megz
						element: document.getElementById('file-uploader-ftp'),
						action: '/valums-file-upload/server/user-<?php echo $type; ?>.php',
						// pour chaque image envoyée
						onComplete: function(id, fileName, responseJSON){
							if(responseJSON.success){
								// remplacement du texte par défaut par ma sauce perso
								$("li.qq-upload-success:not(.lpedited)").each(function(){
									var file=responseJSON.filename;
									var html='Fichier <b>'+file+'"</b> bien enregistré.';
									$(this).html(html).addClass('info mini').css('padding', '3px 5px');
								}).addClass('lpedited');
							}
							// rechargement du cadre
							if(!$(".qq-upload-spinner:visible").length && !$(".qq-upload-fail:visible").length)
								window.location.href='user-file-browser.php?type=<?php echo $type; ?>';
							// message d'erreur si certains ont fonctionné
							if($(".qq-upload-fail:visible").length && $(".qq-upload-success:visible").length){
								html='<p class="erreur">Certains éléments ont bien été envoyés, mais pas tous. <a href="user-file-browser.php?type=<?php echo $type; ?>" >Cliquez ici pour actualiser le cadre et afficher les nouveaux éléments</a>.</p>';
								$("#uploadLog").html(html);
							}
						},
						debug: true
					});
				}
				window.onload = createUploader;
			</script>
			<div id="uploadLog"></div>
			<br />

			<!-- OPERATIONS -->
			<?php
            if ('delete' == $_GET['operation']) {
                $file = $_GET['file'];
                if (!file_exists($dossier.$file)) {
                    echo '<p class="erreur"> Erreur : fichier non trouvé</p>';
                } elseif (strpos(' '.$file, '../')) {
                    echo '<p class="erreur"> Erreur tentative de piratage</p>';
                } else {
                    if (unlink($dossier.$file)) {
                        echo '<p class="info">Fichier '.$file.' supprimé</p>';
                    } else {
                        echo '<p class="erreur"> Erreur technique lors de la suppression.</p>';
                    }
                }
            } ?>

			<table>
				<thead>
					<th></th>
					<th></th>
					<th>Nom</th>
					<th>Poids</th>
					<th>Ajouté le</th>
					<!--<th>Créé le</th>-->
				</thead>
				<?php
                // tableau des fichiers
                $tabFichiers = [];
    $extTab = $p_ftpallowed;

    // restrion au type image
    if ('image' === $type) {
        $extTab = ['jpg', 'jpeg', 'png'];
    }

    // ouverture du dossier demande
    $handle = opendir($dossier);

    while ($fichier = readdir($handle)) {
        $filepath = $dossier.$fichier;
        $extension = strtolower(pathinfo($fichier, \PATHINFO_EXTENSION));

        // on ne liste pas les dossiers
        if (is_dir($filepath)) {
            continue;
        }

        // on ne liste pas ce qui ne matche pas les extensions
        if (!in_array($extension, $extTab, true)) {
            continue;
        }

        if ('image' === $type) {
            $icon = $filepath;
        } else {
            switch ($extension) {
                            case 'jpg':
                            case 'png':
                            case 'gif':
                            case 'jpeg':
                                $icon = '/img/base/image.png';
                                break;
                            case 'odt':
                                $icon = '/img/base/OOffice.jpg';
                                break;
                            case 'doc':
                                $icon = '/img/base/iconeDoc.gif';
                                break;
                            case 'pdf':
                                $icon = '/img/base/pdf.png';
                                break;
                            default:
                                $icon = '/img/base/fichier.png';
                                break;
                        }
        }

        $tabFichiers[] = [
                        'file' => $fichier,
                        'filepath' => $filepath,
                        'url' => substr($filepath, 3),
                        'size' => filesize($filepath),
                        'mtime' => filemtime($filepath),
                        'ctime' => filectime($filepath),
                        'icon' => $icon,
                    ];
    }

    closedir($handle);

    // tri par mtime descendant
    usort($tabFichiers, function ($fileA, $fileB) {
        if ($fileA === $fileB) {
            return 0;
        }

        return $fileA['mtime'] > $fileB['mtime'] ? -1 : 1;
    });

    foreach ($tabFichiers as $fichier) {
        echo '
					<tr>
						<td style="width:30px; text-align:center">
							<img src="/img/base/add.png" alt="Insérer" title="Insérer ce fichier" style="cursor:pointer" onclick="inserer(\''.$fichier['url'].'\')" />
						</td>
						<td>
							'.('image' == $type ?
                            '<a class="fancybox" href="'.$fichier['icon'].'" title="'.html_utf8($fichier['file']).'"><img src="'.$fichier['icon'].'" alt="" title="Aperçu de cette image" style="max-height:25px; max-width:30px; padding:2px 5px 2px 0" /></a>'
                            :
                            '<a target="_blank" href="'.$fichier['filepath'].'" title="Ouvrir '.html_utf8($fichier['file']).' dans une nouvelle fenêtre"><img src="'.$fichier['icon'].'" alt="" title="" style="max-height:25px; max-width:30px; padding:2px 5px 2px 0" /></a>'
                        ).'
						</td>
						<td>
							'.('image' == $type ?
                            '<a class="fancybox" href="'.$fichier['icon'].'" title="'.html_utf8($fichier['file']).'">'.substr($fichier['file'], 0, 70).'</a>'
                            :
                            '<a target="_blank" href="'.$fichier['filepath'].'" title="Ouvrir '.html_utf8($fichier['file']).' dans une nouvelle fenêtre">'.substr($fichier['file'], 0, 70).'</a>'
                        ).'
						</td>
						<td>
							<span style="display:none">'.$fichier['size'].'</span>
							'.formatSize($fichier['size']).'
						</td>
						<td>
							<span style="display:none">'.$fichier['mtime'].'</span>'
                        // Supprimer : le lien est intégré ici pour raison graphique
                        .'<img class="file-delete" src="/img/base/bullet_delete.png" title="Supprimer" alt="" style="float:right; cursor:pointer;" />'
                        .date('d/m/y H:i', $fichier['mtime']).'
						</td>
					</tr>';
    } ?>
            </table>
		</div>
	</body>
	</html>
	<?php
}
