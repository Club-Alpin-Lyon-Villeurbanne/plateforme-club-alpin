<?php

use App\Ftp\FtpFile;
use App\Legacy\LegacyContainer;

require __DIR__.'/../../app/includes.php';

if (admin()) {
    $targetDir = __DIR__.'/../../../public/'.$_GET['dossier'].'/';

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    $uploader = new qqFileUploader(FtpFile::getAllowedExtensions(), 100 * 1024 * 1024);
    $result = $uploader->handleUpload($targetDir);

    $tmpfilename = $result['filename'];
    $filename = formater($result['filename'], 4);

    if ($filename !== $tmpfilename && is_file($tmpfilename)) {
        if ($filename === strtolower($tmpfilename)) {
            LegacyContainer::get('legacy_fs')->rename($targetDir.$tmpfilename, $targetDir.$tmpfilename);
        } else {
            LegacyContainer::get('legacy_fs')->copy($targetDir.$tmpfilename, $targetDir.$filename);
            LegacyContainer::get('legacy_fs')->remove($targetDir.$result['filename']);
            $result['filename'] = $filename;
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
