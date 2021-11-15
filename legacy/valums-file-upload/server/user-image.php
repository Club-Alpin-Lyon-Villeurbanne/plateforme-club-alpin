<?php

include __DIR__.'/../../app/includes.php';

$errTab = [];
$result = $targetDir = null;

// $errTab[]="Test";
if (!user()) {
    $errTab[] = 'User non connecté';
} elseif (!$_SESSION['user']['id_user']) {
    $errTab[] = 'ID manquant';
}

if (0 === count($errTab)) {
    $targetDir = __DIR__.'/../../../public/ftp/user/'.(int) ($_SESSION['user']['id_user']).'/images/';

    // Handle file uploads via XMLHttpRequest
    include __DIR__.'/vfu.classes.php';

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

if ($result && 0 === count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($tmpfilename, 4));

    // si le nom formaté diffère de l'original
    if ($filename != $tmpfilename) {
        // debug : copie impossible si le nom de fichier est juste une variante de CASSE
        // donc dans ce cas on le RENOMME
        if ($filename == strtolower($tmpfilename)) {
            if (!rename($targetDir.$tmpfilename, $targetDir.$filename)) {
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

    // redimensionnement des images
    if (0 === count($errTab)) {
        $size = getimagesize($targetDir.$filename);
        if ($size[0] > 600 || $size[1] > 800) {
            include __DIR__.'/../../app/redims.php';
            $W_max = 600;
            $H_max = 800;
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
}

// envoi du résultat :
if (count($errTab) > 0) {
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
