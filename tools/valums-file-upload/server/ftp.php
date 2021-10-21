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
    $allowedExtensions = $p_ftpallowed;
    // max file size in bytes
    $sizeLimit = 100 * 1024 * 1024;

    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
    $result = $uploader->handleUpload($targetDir);

    $tmpfilename = $result['filename'];
    $filename = formater($result['filename'], 4);
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
                $errTab[] = 'Erreur de copie de '.$targetDirRel.$result['filename']." \n vers ".$targetDirRel.$filename;
            }
        }
    }

    // envoi du résultat :
    if (count($errTab)) {
        $result = ['success' => 0, 'error' => implode(', ', $errTab)];
    }
    // si pas d'erreur, le nom de ficier est remplacé par sa version formatée

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);

    /* *

    $log.="\n  errTab :";
    foreach($errTab as $key=>$value)
        $log.="\n $key = $value";


    $fp = fopen('dev.txt', 'w');fwrite($fp, $log);fclose($fp);
    /* */
}
