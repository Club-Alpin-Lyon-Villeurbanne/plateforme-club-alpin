<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define('DS', \DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__, 3).DS);				// Racine
include ROOT.'app'.DS.'includes.php';

$errTab = [];

// $errTab[]="Test";
if (!user() && !admin()) {
    $errTab[] = 'User non connecté';
} elseif (!$_SESSION['user']['id_user'] && !admin()) {
    $errTab[] = 'ID manquant';
}

if (admin()) {
    $_SESSION['user']['id_user'] = 0;
}

if (!count($errTab)) {
    $targetDir = 'ftp/user/'.(int) ($_SESSION['user']['id_user']).'/images/'; // depuis la racine
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    } else {
        chmod($targetDir, 0777);
    }
    $targetDirRel = '../../../'.$targetDir; // chemin relatif

    // Handle file uploads via XMLHttpRequest
    include 'vfu.classes.php';

    // list of valid extensions, ex. array("jpeg", "xml", "bmp")
    $allowedExtensions = ['jpeg', 'jpg', 'png',
                               'JPEG', 'JPG', 'PNG', ];
    // max file size in bytes
    $sizeLimit = 5 * 1024 * 1024;

    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
    $result = $uploader->handleUpload($targetDir);

    if ($result['error']) {
        $errTab[] = $result['error'];
    }
}

if (!count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($tmpfilename, 4));

    // si le nom formaté diffère de l'original
    if ($filename != $tmpfilename) {
        // debug : copie impossible si le nom de fichier est juste une variante de CASSE
        // donc dans ce cas on le RENOMME
        if ($filename == strtolower($tmpfilename)) {
            if (!rename($targetDirRel.$tmpfilename, $targetDirRel.$filename)) {
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

    // redimensionnement des images
    if (!count($errTab)) {
        $size = getimagesize($targetDirRel.$filename);
        if ($size[0] > 600 || $size[1] > 800) {
            include APP.'redims.php';
            $W_max = 600;
            $H_max = 800;
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
