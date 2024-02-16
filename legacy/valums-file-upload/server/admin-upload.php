<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;
use App\Ftp\FtpFile;

require __DIR__.'/../../app/includes.php';

$errTab = [];
$result = $targetDir = $filename = null;
$ftpPath = LegacyContainer::getParameter('legacy_ftp_path');
$type = htmlspecialchars($_GET['type']) ?? 'image';

if (!user() && !admin()) {
    $errTab[] = 'User non connecté';
}

if (!in_array($type, ['image', 'file'])) {
    $errTab[] = 'Type de fichier non reconnu';
}

if (0 === count($errTab)) {
    $dossier = isset($_GET['dossier']) || array_key_exists('dossier', $_GET) ? urldecode($_GET['dossier']).'/' : 'user/0/'.$type.'s/';
    $targetDir = $ftpPath.$dossier;
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $targetDir));
        }
    } else {
        chmod($targetDir, 0755);
    }

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    if ($type === 'file') {
        $uploader = new qqFileUploader(FtpFile::getAllowedExtensions());
    } else {
        $uploader = new qqFileUploader();
    }
    $result = $uploader->handleUpload($targetDir);

    if (isset($result['error']) || array_key_exists('error', $result)) {
        $errTab[] = $result['error'];
    }
}

if (0 === count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($tmpfilename, 4));

    // si le nom formaté diffère de l'original
    if ($filename != $tmpfilename) {
        // debug : copie impossible si le nom de fichier est juste une variante de CASSE
        // donc dans ce cas on le RENOMME
        if ($filename === strtolower($tmpfilename)) {
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
                // sauf erreur le nom de fichier est remplacé par sa version formatée
                $result['filename'] = $filename;
            } else {
                $errTab[] = 'Erreur de copie de '.$targetDir.$result['filename']." \n vers ".$targetDir.$filename;
            }
        }
    }

    if ($type === 'image') {
        // redimensionnement des images
        if (0 === count($errTab)) {
            if (!ImageManipulator::resizeImage(600, 800, $targetDir.$filename, $targetDir.$filename, true)) {
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
