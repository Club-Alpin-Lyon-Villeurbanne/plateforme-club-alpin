<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;

require __DIR__ . '/../../app/includes.php';

$errTab = [];
$result = $targetDir = null;

if (!user()) {
    $errTab[] = 'User non connecté';
}

if (0 === count($errTab)) {
    $targetDir = LegacyContainer::getParameter('legacy_ftp_path') . 'user/' . getUser()->getId() . '/images/';

    // Handle file uploads via XMLHttpRequest
    require __DIR__ . '/vfu.classes.php';

    $uploader = new qqFileUploader();
    $result = $uploader->handleUpload($targetDir);

    if (isset($result['error'])) {
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
        if ($filename === strtolower($tmpfilename)) {
            if (!rename($targetDir . $tmpfilename, $targetDir . $filename)) {
                $errTab[] = 'Erreur de renommage de ' . $targetDir . $tmpfilename . " \n vers " . $targetDir . $filename;
            }
        } else {
            // copie du fichier avec nvx nom
            if (copy($targetDir . $tmpfilename, $targetDir . $filename)) {
                // suppression de l'originale
                if (is_file($targetDir . $result['filename'])) {
                    unlink($targetDir . $result['filename']);
                }
                // sauf erreur le nom de ficier est remplacé par sa version formatée
                $result['filename'] = $filename;
            } else {
                $errTab[] = 'Erreur de copie de ' . $targetDir . $result['filename'] . " \n vers " . $targetDir . $filename;
            }
        }
    }

    if (0 === count($errTab)) {
        if (!ImageManipulator::resizeImage(600, 800, $targetDir . $filename, $targetDir . $filename, true)) {
            $errTab[] = 'Image : Erreur de redim';
        }
    }
}

// envoi du résultat :
if (count($errTab) > 0) {
    $result = ['success' => 0, 'error' => implode(', ', $errTab)];
}

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
