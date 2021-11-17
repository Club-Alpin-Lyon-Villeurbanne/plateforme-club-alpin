<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;

require __DIR__.'/../../app/includes.php';

$MAX_DIMS = LegacyContainer::getParameter('legacy_env_MAX_IMAGE_SIZE');

if (admin()) {
    $targetDir = __DIR__.'/../../../public/'.$_GET['dossier'].'/';

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    $uploader = new qqFileUploader();
    $result = $uploader->handleUpload($targetDir);

    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($result['filename'], 4));

    if ($filename !== $tmpfilename && is_file($tmpfilename)) {
        if ($filename === strtolower($tmpfilename)) {
            LegacyContainer::get('legacy_fs')->rename($targetDir.$tmpfilename, $targetDir.$tmpfilename);
        } else {
            LegacyContainer::get('legacy_fs')->copy($targetDir.$tmpfilename, $targetDir.$filename);
            LegacyContainer::get('legacy_fs')->remove($targetDir.$result['filename']);
            $result['filename'] = $filename;
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        if (!ImageManipulator::resizeImage($MAX_DIMS, $MAX_DIMS, $targetDir.$filename, $targetDir.$filename, true)) {
            $errTab[] = 'Image : Erreur de redim';
        }
    }

    // envoi du résultat :
    if (isset($errTab) && count($errTab) > 0) {
        $result = ['success' => 0, 'error' => implode(', ', $errTab)];
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
