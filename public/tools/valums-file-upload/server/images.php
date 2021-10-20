<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define('DS', \DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__, 3).DS);				// Racine
include ROOT.'app'.DS.'includes.php';

if (admin()) {
    $targetDir = $_GET['dossier'].'/'; // depuis la racine
    $targetDirRel = '../../../'.$targetDir; // chemin relatif

    // Handle file uploads via XMLHttpRequest
    include 'vfu.classes.php';

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
            if (!rename($targetDirRel.$tmpfilename, $targetDirRel.$tmpfilename)) {
                $errTab[] = 'Erreur de renommage de '.$targetDirRel.$tmpfilename." \n vers ".$targetDir.$filename;
            }
        } else {
            // copie du fichier avec nvx nom
            if (copy($targetDirRel.$tmpfilename, $targetDirRel.$filename)) {
                // suppression de l'originale
                if (is_file($targetDirRel.$result['filename'])) {
                    unlink($targetDirRel.$result['filename']);
                }
                // sauf erreur le nom de ficier est remplacé par sa version formatée
                $result['filename'] = $filename;
            } else {
                $errTab[] = 'Erreur de copie de '.$targetDirRel.$result['filename']." \n vers ".$targetDir.$filename;
            }
        }
    }
    $ext = strtolower(substr(strrchr($filename, '.'), 1));

    // redimensionnement des images
    if (!count($errTab) && ('jpg' == $ext || 'jpeg' == $ext || 'png' == $ext)) {
        $size = getimagesize($targetDirRel.$filename);
        if ($size[0] > $p_max_images_dimensions_before_redim || $size[1] > $p_max_images_dimensions_before_redim) {
            include APP.'redims.php';
            $W_max = $p_max_images_dimensions_before_redim;
            $H_max = $p_max_images_dimensions_before_redim;
            $rep_Dst = $targetDirRel;
            $img_Dst = $filename;
            $rep_Src = $targetDirRel;
            $img_Src = $filename;
            // redim 1
            if (!fctredimimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                $errTab[] = 'Image : Erreur de redim';
            }
        }
    }

    // envoi du résultat :
    if (count($errTab)) {
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
