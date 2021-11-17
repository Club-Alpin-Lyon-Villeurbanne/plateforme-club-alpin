<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;

require __DIR__.'/../../app/includes.php';

$errTab = [];
$result = $targetDir = null;

// $errTab[]="Test";
if (!user()) {
    $errTab[] = 'User non connecté';
}

if (0 === count($errTab)) {
    $targetDir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/images/';

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    $uploader = new qqFileUploader();
    $result = $uploader->handleUpload($targetDir);

    if ($result['error']) {
        $errTab[] = $result['error'];
    }
}

if ($result && 0 === count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($tmpfilename, 4));

    if ($filename !== $tmpfilename && is_file($tmpfilename)) {
        if ($filename === strtolower($tmpfilename)) {
            LegacyContainer::get('legacy_fs')->rename($targetDir.$tmpfilename, $targetDir.$filename);
        } else {
            LegacyContainer::get('legacy_fs')->copy($targetDir.$tmpfilename, $targetDir.$filename);
            LegacyContainer::get('legacy_fs')->remove($targetDir.$result['filename']);
            // sauf erreur le nom de ficier est remplacé par sa version formatée
            $result['filename'] = $filename;
        }
    }

    if (0 === count($errTab)) {
        if (!ImageManipulator::resizeImage(600, 800, $targetDir.$filename, $targetDir.$filename, true)) {
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
