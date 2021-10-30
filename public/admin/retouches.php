<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define('DS', \DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__).DS);				// Racine
include ROOT.'app'.DS.'includes.php';

if (!admin()) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Votre session administrateur a expiré';
    exit();
}

    ?><!doctype html>
	<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>RETOUCHES D'IMAGES</title>

		<link rel="stylesheet" media="screen" type="text/css" title="Design" href="../css/base.css">
		<link rel="stylesheet" media="screen" type="text/css" title="Design" href="../css/admin.css">
		<link rel="stylesheet" href="../tools/fancybox/jquery.fancybox.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="../css/ui-cupertino/jquery-ui-1.8.18.custom.css" type="text/css"  media="screen" />

		<!-- jquery -->
		<script type="text/javascript" src="../js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="../js/jquery-ui-1.8.16.full.min.js"></script>
		<script type="text/javascript" src="../js/fonctionsAdmin.js"></script>
		<!-- fancybox -->
		<script type="text/javascript" src="../tools/fancybox/jquery.fancybox.pack.js" charset="utf-8"></script>
		<!-- <script type="text/javascript" src="../tools/fancybox/jquery.mousewheel-3.0.4.pack.js" charset="utf-8"></script> -->
	</head>
	<body style="background:#DEDEDE; border:none;">

		<?php
        $src = html_utf8($_GET['src']);
        if ('OK' == $_GET['maj']) {
            echo '<p class="info">Votre image a bien été retouchée. Actualisez le dossier FTP pour la voir dans la liste.</p>';
        }
        if (!$src) {
            ?>
			<p>
				Pour retoucher vos images, naviguez dans le dossier FTP et cliquez sur le bouton <img src="../img/base/image_edit.png" alt="" title="" style="vertical-align:middle" />.
				<br />Seules les images <b>.jpg</b> et <b>.png</b> peuvent être retouchées.
			</p>
			<?php
        }

        // SRC transmis
        else {
            // err fichier inexistant
            if (!is_file('../'.$src)) {
                echo '<p class="erreur">Erreur : fichier <b>'.$src.'</b> introuvable.</p>';
            }
            // ok
            else {
                // si le fichier visé n'est pas déjà en standby dans l'éditeur...
                // $filename=array_pop(explode('/', $src));
                $filename = substr(strrchr($src, '/'), 1);

                // if(!is_file('../ftp/transit/retouches/'.$filename)){
                // suppression des autres documents en standby
                $opendir = opendir('../ftp/transit/retouches');
                while ($tmpFile = readdir($opendir)) {
                    if ('.' != $tmpFile && '..' != $tmpFile && 'index.php' != $tmpFile && '.htaccess' != $tmpFile && strlen($tmpFile)) {
                        unlink('../ftp/transit/retouches/'.$tmpFile);
                    }
                }
                // copie du fichier
                if (!copy('../'.$src, '../ftp/transit/retouches/'.$filename)) {
                    echo '<p class="erreur">Copie échouée...</p>';
                    exit();
                }
                // }
                // $ext=strtolower(array_pop(explode('.', $filename)));
                $ext = substr(strrchr($filename, '.'), 1);

                // en cas de remplacement de nom, vérifier qu'on écrase pas un fichier précédemment créé
                $basename = substr($src, 0, -1 * (strlen($ext) + 1));
                $replaceName = $basename.'-edit.'.$ext;
                if (file_exists('../'.$replaceName)) {
                    // echo 'existe';
                    $existe = true;
                    for ($i = 2; $existe; ++$i) {
                        if (!file_exists('../'.$basename.'-edit-'.$i.'.'.$ext)) {
                            $existe = false;
                            $replaceName = $basename.'-edit-'.$i.'.'.$ext;
                        }
                    }
                    // echo 'replaceName : '.$replaceName;
                } ?>

				<!-- jcrop -->
				<link rel="stylesheet" media="screen" type="text/css" title="Design" href="../css/jquery.Jcrop.min.css">
				<script type="text/javascript" src="../js/jquery.Jcrop.min.js"></script>
				<script type="text/javascript" src="../js/admin-retouches.js" charset="utf-8"></script>

				<table id="lp-image-editor">
					<!-- tools -->
					<tr>
						<td>
							<div class="lpie-tools" style="float:left; padding:5px 0 3px 0;" >
								<input type="radio" id="lpie-tool-arrow" value="arrow" name="tool" checked="checked" /><label for="lpie-tool-arrow"><img src="../img/base/cursor.png" alt="" title="" style="vertical-align:middle" /></label>
								<input type="radio" id="lpie-tool-crop" value="crop"  name="tool" /><label for="lpie-tool-crop"><img src="../img/base/crop.png" alt="" title="" style="vertical-align:middle" /> Recadrer</label>
								<input type="radio" id="lpie-tool-resize" value="resize"  name="tool" /><label for="lpie-tool-resize"><img src="../img/base/resize.png" alt="" title="" style="vertical-align:middle" /> Redimensionner</label>
							</div>
							<div id="lpie-tool-resize-block" style="display:none; float:left; padding:0px 15px 6px 10px; border:1px solid silver; border-radius:6px; background:white;" >
								<label for="lpie-tool-resize-show">Taille de l'image :</label>
								<input type="text" id="lpie-tool-resize-show" style="border:0; background:none;" value="100%" disabled="disabled" />
								<div id="lpie-tool-resize-select"></div>
							</div>
							<div class="lpie-tools" style="float:right; padding:5px 0 3px 0;" >
								<input type="radio" id="lpie-tool-preview" value="preview"  name="tool" /><label for="lpie-tool-preview"><img src="../img/base/camera.png" alt="" title="" style="vertical-align:middle" /> Aperçu (popup)</label>
								<input type="radio" id="lpie-tool-save" value="save"  name="tool" /><label for="lpie-tool-save"><img src="../img/base/save.png" alt="" title="" style="vertical-align:middle; height:16px" /> Enregistrer</label>
							</div>
						</td>
					</tr>
					<!-- preview -->
					<tr>
						<td>
							<div class="source">
								<p>
									<span  class="stats">
										Dimensions de l'image d'origine :
										<input type="text" class="wOrig" style="border:0; background:none; width:35px; text-align:right;" value="" disabled="disabled" /> *
										<input type="text" class="hOrig" style="border:0; background:none; width:35px; text-align:left;" value="" disabled="disabled" />
									</span>

									<span  class="stats2">
										&nbsp;&nbsp; | &nbsp;&nbsp;
										Dimensions de l'image retouchée :
										<input type="text" class="wDest" style="border:0; background:none; width:35px; text-align:right;" value="" disabled="disabled" /> *
										<input type="text" class="hDest" style="border:0; background:none; width:35px; text-align:left;" value="" disabled="disabled" />
										<!-- vars finales -->
										<input type="hidden" class="wDestNocrop" style="border:0; background:none; width:35px; text-align:right;" value="" disabled="disabled" />
										<input type="hidden" class="hDestNocrop" style="border:0; background:none; width:35px; text-align:right;" value="" disabled="disabled" />
										<input type="hidden" class="xDest" style="border:0; background:none; width:35px; text-align:right;" value="0" disabled="disabled" />
										<input type="hidden" class="yDest" style="border:0; background:none; width:35px; text-align:right;" value="0" disabled="disabled" />
									</span>
								</p>
								<img src="../ftp/transit/retouches/<?php echo $filename; ?>" class="lp-image-editor-source" alt="IMAGE" title="Image d'origine" onload="var toframe=setTimeout('window.parent.actu_iframe()', 200);" />
							</div>
						</td>
					</tr>
				</table>

				<div class="mybox-up" id="mybox-loading" style="display:block;"><p>Chargement en cours, veuillez patienter...<br /><br /><img src="../img/base/loading.gif" alt="" title="" /></p></div>
				<div class="mybox-up" id="mybox-save" style="display:none;">
					<div class="buttonset">
						<input type="button" value="Remplacer l'image actuelle" name="save-replace" />
						<input type="button" value="Renommer en <?php echo substr(strrchr($replaceName, '/'), 1); ?>" name="save-rename" />
						<input type="button" value="Annuler" name="save-cancel" />

						<input type="hidden" value="<?php echo '../'.$src; ?>" name="save-replace-filename" />
						<input type="hidden" value="<?php echo '../'.$replaceName; ?>" name="save-rename-filename" />
					</div>
				</div>
				<div class="mybox-down" style="display:block;"></div>
				<?php
            }
        }
        ?>
		<script type="text/javascript">
		window.parent.actu_iframe();
		</script>
	</body>
	</html>
	<?php

?>
