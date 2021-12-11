<?php

use App\Legacy\LegacyContainer;

require __DIR__.'/../../app/includes.php';

$MAX_DIMS = LegacyContainer::getParameter('legacy_env_MAX_IMAGE_SIZE');

if (admin()) {
    $targetDir = __DIR__.'/../../../public/'.$_GET['dossier'].'/';

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    // list of valid extensions, ex. array("jpeg", "xml", "bmp")
    $allowedExtensions = ['jpeg', 'jpg', 'gif', 'png', 'bmp',
                            'JPEG', 'JPG', 'GIF', 'PNG', 'BMP',	];
    // max file size in bytes
    $sizeLimit = 100 * 1024 * 1024;

    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
    $result = $uploader->handleUpload($targetDir);

    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($result['filename'], 4));
    // $filename='test.jpg';
    // si le nom formaté diffère de l'original
    if ($filename != $tmpfilename) {
        // debug : copie impossible si le nom de fichier est juste une variante de CASSE
        // donc dans ce cas on le RENOMME
        if ($filename == strtolower($tmpfilename)) {
            if (!rename($targetDir.$tmpfilename, $targetDir.$tmpfilename)) {
                $errTab[] = 'Erreur de renommage de '.$targetDir.$tmpfilename." \n vers ".$targetDir.$filename;
            }
        } else {
            // copie du fichier avec nvx nom
            if (copy($targetDir.$tmpfilename, $targetDir.$filename)) {
                // suppression de l'originale
                if (is_file($targetDir.$result['filename'])) {
                    unlink($targetDir.$result['filename']);
                }
                // sauf erreur le nom de ficier est remplacé par sa version formatée
                $result['filename'] = $filename;
            } else {
                $errTab[] = 'Erreur de copie de '.$targetDir.$result['filename']." \n vers ".$targetDir.$filename;
            }
        }
    }
    $ext = strtolower(substr(strrchr($filename, '.'), 1));

    // redimensionnement des images
    if ((!isset($errTab) || 0 === count($errTab)) && ('jpg' == $ext || 'jpeg' == $ext || 'png' == $ext)) {
        $size = getimagesize($targetDir.$filename);
        if ($size[0] > $MAX_DIMS || $size[1] > $MAX_DIMS) {
            require __DIR__.'/../../app/redims.php';
            $W_max = $MAX_DIMS;
            $H_max = $MAX_DIMS;
            $rep_Dst = $targetDir;
            $img_Dst = $filename;
            $rep_Src = $targetDir;
            $img_Src = $filename;
            // redim 1
            if (!fctredimimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                $errTab[] = 'Image : Erreur de redim';
            }
        }
    }

    // envoi du résultat :
    if (isset($errTab) && count($errTab) > 0) {
        $result = ['success' => 0, 'error' => implode(', ', $errTab)];
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);

    /* *

    $log.="\n  errTab :";
    foreach($errTab as $key=>$value)
        $log.="\n $key = $value";


    $fp = fopen('dev.txt', 'w');fwrite($fp, $log);fclose($fp);
    /* */
}
