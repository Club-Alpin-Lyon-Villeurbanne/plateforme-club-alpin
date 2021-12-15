<?php

use App\Ftp\FtpFile;

require __DIR__.'/../../app/includes.php';

if (admin()) {
    $targetDir = __DIR__.'/../../../public/'.$_GET['dossier'].'/';

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    $uploader = new qqFileUploader(FtpFile::getAllowedExtensions(), 100 * 1024 * 1024);
    $result = $uploader->handleUpload($targetDir);

    $tmpfilename = $result['filename'];
    $filename = formater($result['filename'], 4);
    // $filename='test.jpg';
    // si le nom formaté diffère de l'original
    if ($filename != $tmpfilename) {
        // debug : copie impossible si le nom de fichier est juste une variante de CASSE
        // donc dans ce cas on le RENOMME
        if ($filename === strtolower($tmpfilename)) {
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

    // envoi du résultat :
    if (isset($errTab) && count($errTab) > 0) {
        $result = ['success' => 0, 'error' => implode(', ', $errTab)];
    }
    // si pas d'erreur, le nom de ficier est remplacé par sa version formatée

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
