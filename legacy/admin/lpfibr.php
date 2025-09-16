<?php

use App\Ftp\FtpFile;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

require __DIR__ . '/../app/includes.php';

// bien connecté ?
$id_user = getUser()->getId();
if (!$id_user && !isGranted(SecurityConstants::ROLE_ADMIN) && !isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'ERREUR : id invalide';
    exit;
}

if (isGranted(SecurityConstants::ROLE_ADMIN) || isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    // recuperation du dossier
    $type = $_GET['type'];
    $dossier = null;
    $ftpPath = LegacyContainer::getParameter('legacy_ftp_path');
    if ('image' == $type) {
        $dossier = isset($_GET['dossier']) || array_key_exists('dossier', $_GET) ? urldecode($_GET['dossier']) . '/' : 'images/';
        $fullPath = $ftpPath . $dossier;
    } elseif ('file' == $type) {
        $dossier = 'telechargements/';
        $fullPath = $ftpPath . $dossier;
    } else {
        echo "ERREUR : type invalide ($type / $dossier)";
        exit;
    }

    // première visite : dossier inexistant
    if (!file_exists($fullPath)) {
        if (!mkdir($concurrentDirectory = $fullPath) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    ?><!DOCTYPE html>
	<html lang="fr">
		<head>
            <meta charset="UTF-8">
            <title><?php if ('image' == $type) {
                echo 'Vos images en ligne';
            } else {
                echo 'Vos fichiers en téléchargements';
            } ?></title>

            <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('styles'); ?>
            <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles'); ?>

            <!-- valums file upload -->
            <link href="/css/ftp-fileuploader.css" rel="stylesheet" type="text/css">

            <!-- commnuicate with window arbo-->
            <script type="text/javascript" src="/tools/tinymce/tiny_mce_popup.js"></script>

            <!-- jquery
            <script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>
            -->
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
            <script type="text/javascript" src="/js/fonctionsAdmin.js"></script>

            <!-- fancybox -->
            <link rel="stylesheet" href="/tools/fancybox/jquery.fancybox.css" type="text/css" media="screen" />
            <script type="text/javascript" src="/tools/fancybox/jquery.fancybox.pack.js" charset="utf-8"></script>
            <!-- <script type="text/javascript" src="/tools/fancybox/jquery.mousewheel-3.0.4.pack.js" charset="utf-8"></script> -->

            <!-- Datatables -->
            <link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.sobre.css" type="text/css" media="screen" />
            <script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>


            <script type="text/javascript">
                // jquery
                $(document).ready(function() {

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
                            window.location = 'lpfibr.php?dossier=<?php echo urlencode($dossier); ?>&type=<?php echo $type; ?>&operation=delete&file='+file;
                    });


                });

                // tinyMCE
                var FileBrowserDialogue = {
                    init : function () {
                        // Here goes your code for setting your custom things onLoad.
                    },
                    mySubmit : function (url) {
                        // var URL = document.my_form.my_field.value;
                        var win = tinyMCEPopup.getWindowArg("window");

                        // insert information now
                        win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = url;

                        // are we an image browser
                        if (typeof(win.ImageDialog) != "undefined") {
                            // we are, so update image dimensions...
                            if (win.ImageDialog.getImageData)
                                win.ImageDialog.getImageData();

                            // ... and preview if necessary
                            if (win.ImageDialog.showPreviewImage)
                                win.ImageDialog.showPreviewImage(url);
                        }

                        // close popup window
                        tinyMCEPopup.close();
                    }
                }
                tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);

                function inserer(url) {
                    FileBrowserDialogue.mySubmit(url);
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
                        Déposez ici les images que vous souhaitez insérer dans vos articles.
                        La plupart des formats d\'images sont supportés, poids maximum : 20Mo.
                        ';
                        }
    if ('file' == $type) {
        echo "
                                    Déposez ici les fichiers que vous souhaitez proposer en téléchargement.<br />
                                    Ext. autorisées : <span style='font-size: 0.6rem'>" . implode(', ', FtpFile::getAllowedExtensions()) . '</span>
                                    ';
    } ?>
                </p>

                <!-- valums file upload -->
                <div id="file-uploader-ftp"><noscript>L'envoi de fichier nécessite javascript</noscript></div>
                <script src="/tools/valums-file-upload/js/fileuploader.js" type="text/javascript"></script>
                <script>
                    function createUploader(){
                        var uploader = new qq.FileUploader({

                            sizeLimit: 20 * 1024 * 1024,
                            element: document.getElementById('file-uploader-ftp'),
                            action: '/valums-file-upload/server/admin-upload.php?type=<?php echo $type; ?>&dossier=<?php echo urlencode($dossier); ?>',
                            // pour chaque image envoyée
                            onComplete: function(id, fileName, responseJSON){
                                if(responseJSON.success){
                                    // remplacement du texte par défaut par ma sauce perso
                                    $("li.qq-upload-success:not(.lpedited)").each(function(){
                                        var file=responseJSON.filename;
                                        var html='Fichier <b>'+file+'</b> bien enregistré.';
                                        $(this).html(html).addClass('info mini').css('padding', '3px 5px');
                                    }).addClass('lpedited');
                                }
                                // rechargement du cadre
                                if(!$(".qq-upload-spinner:visible").length && !$(".qq-upload-fail:visible").length)
                                    window.location.href='lpfibr.php?dossier=<?php echo urlencode($dossier); ?>&type=<?php echo $type; ?>';
                                // message d'erreur si certains ont fonctionné
                                if($(".qq-upload-fail:visible").length && $(".qq-upload-success:visible").length){
                                    html='<p class="erreur">Certains éléments ont bien été envoyés, mais pas tous. <a href="/lpfibr.php?dossier=<?php echo urlencode($dossier); ?>&type=<?php echo $type; ?>" >Cliquez ici pour actualiser le cadre et afficher les nouveaux éléments</a>.</p>';
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
                    $operation = isset($_GET['operation']) || array_key_exists('operation', $_GET) ? $_GET['operation'] : null;
    if ('delete' == $operation) {
        $file = $_GET['file'];
        if (!file_exists($fullPath . $file)) {
            echo '<p class="erreur"> Erreur : fichier non trouvé</p>';
        } elseif (strpos(' ' . $file, '../')) {
            echo '<p class="erreur"> Erreur tentative de piratage</p>';
        } else {
            if (unlink($fullPath . $file)) {
                echo '<p class="info">Fichier ' . $file . ' supprimé</p>';
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

    $extTab = [];
    // extensions autorisées ici en fonction du type demandé
    if ('image' == $type) {
        $extTab = FtpFile::getAllowedImagesExtension();
    } elseif ('file' == $type) {
        $extTab = FtpFile::getAllowedExtensions();
    }

    // ouverture du dossier demande
    $handle = opendir($fullPath);
    $j = 0; // compte des fichiers
    while ($fichier = readdir($handle)) {
        // ext
        $ext = strtolower(pathinfo($fichier, \PATHINFO_EXTENSION));
        // Selection des fichiers a afficher ou pas
        if (in_array($ext, $extTab, true)) {
            // est-ce bien un fichier
            if (!is_dir($fullPath . $fichier)) {
                $tabFichiers[$j] = $fichier;
                ++$j;
            }
        }
    }
    closedir($handle);
    // AFFICHAGE DU FICHIER
    $k = 0;
    $package = new PathPackage('/ftp', new EmptyVersionStrategy());
    foreach ($tabFichiers as $fichier) {
        ++$k;

        // affichage URL
        $relativeUrl = $package->getUrl($dossier . $fichier);

        // icone du fichier ou miniature de l'image
        if ('image' == $type) {
            $icon = $relativeUrl;
        } else {
            $ext = strtolower(substr(strrchr($fichier, '.'), 1));
            switch ($ext) { // on indique sur quelle variable on travaille
                // IMAGES
                case 'jpg':
                case 'png':
                case 'gif':
                case 'bmp':
                case 'webp':
                case 'jpeg': $icon = '/img/base/image.png';
                    break;
                    // TTT DE TEXTES
                case 'odt': $icon = '/img/base/OOffice.jpg';
                    break;
                case 'doc': $icon = '/img/base/iconeDoc.gif';
                    break;
                case 'pdf': $icon = '/img/base/pdf.png';
                    break;
                    // DEFAUT
                default: $icon = '/img/base/fichier.png';
            }
        }

        // taille (octets)
        $fsize = filesize($fullPath . $fichier);

        // date (tsp) de modif
        $mtime = filemtime($fullPath . $fichier);

        // date (tsp) de crea
        $ctime = filemtime($fullPath . $fichier);

        // ///////////////////////
        // AFFICHAGE DE LA LIGNE
        echo '
                                <tr>
                                    <td style="width:30px; text-align:center">
                                        <img src="/img/base/add.png" alt="Insérer" title="Insérer ce fichier" style="cursor:pointer" onclick="inserer(\'' . $relativeUrl . '\')" />
                                    </td>
                                    <td>
                                        ' . ('image' == $type ?
                        '<a class="fancybox" href="' . $icon . '" title="' . html_utf8($fichier) . '"><img src="' . $icon . '" alt="" title="Aperçu de cette image" style="max-height:25px; max-width:30px; padding:2px 5px 2px 0" /></a>'
                    :
                        '<a target="_blank" href="' . $relativeUrl . '" title="Ouvrir ' . html_utf8($fichier) . ' dans une nouvelle fenêtre"><img src="' . $icon . '" alt="" title="" style="max-height:25px; max-width:30px; padding:2px 5px 2px 0" /></a>'
        ) . '
                                    </td>
                                    <td>
                                        ' . ('image' == $type ?
        '<a class="fancybox" href="' . $icon . '" title="' . html_utf8($fichier) . '">' . substr($fichier, 0, 70) . '</a>'
        :
        '<a target="_blank" href="' . $relativeUrl . '" title="Ouvrir ' . html_utf8($fichier) . ' dans une nouvelle fenêtre">' . substr($fichier, 0, 70) . '</a>'
        ) . '
                                    </td>
                                    <td>
                                        <span style="display:none">' . $fsize . '</span>
                                        ' . formatSize($fsize) . '
                                    </td>
                                    <td>
                                        <span style="display:none">' . $mtime . '</span>'
                    // Supprimer : le lien est intégré ici pour raison graphique
                    . '<img class="file-delete" src="/img/base/bullet_delete.png" title="Supprimer" alt="" style="float:right; cursor:pointer;" />'
                    . date('d/m/y H:i', $mtime) . '
                                    </td>'
                /*
                                    <td>
        <span style="display:none">'.$ctime.'</span>
        '.date('d/m/y H:i', $ctime).'
                                    </td>
                                    */
            . '
                                </tr>';
    } ?>
                </table>
            </div>
        </body>
	</html>
	<?php
}
?>
