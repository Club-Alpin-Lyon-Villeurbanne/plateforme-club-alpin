<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;

require __DIR__.'/../../app/includes.php';

$errTab = [];
$result = $targetDir = $filename = null;

// $errTab[]="Test";
if (!user()) {
    $errTab[] = 'User non connecté';
}

$mode = $_GET['mode'];
$id_article = (int) ($_GET['id_article']);

if ('edit' == $mode && !$id_article) {
    $errTab[] = 'ID sortie manquant';
}

if (0 === count($errTab)) {
    $dir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle';
    LegacyContainer::get('legacy_fs')->mkdir($dir);

    if ('edit' == $mode) {
        $targetDir = __DIR__.'/../../../public/ftp/articles/'.$id_article.'/';
    } else {
        $targetDir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle/';
    }

    LegacyContainer::get('legacy_fs')->mkdir($targetDir);

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    $uploader = new qqFileUploader();
    $result = $uploader->handleUpload($targetDir);

    if ($result['error']) {
        $errTab[] = $result['error'];
    }
}

if (0 === count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = 'figure.jpg';

    if ($filename !== $tmpfilename && is_file($tmpfilename)) {
        if ($filename === strtolower($tmpfilename)) {
            LegacyContainer::get('legacy_fs')->rename($targetDir.$tmpfilename, $targetDir.$filename);
        } else {
            LegacyContainer::get('legacy_fs')->copy($targetDir.$tmpfilename, $targetDir.$filename);
            LegacyContainer::get('legacy_fs')->remove($targetDir.$result['filename']);
            $result['filename'] = $filename;
        }
    }

    if (0 === count($errTab)) {
        $img_Dst = 'wide-'.$filename;

        if (!ImageManipulator::cropImage(665, 365, $targetDir.$filename, $targetDir.$img_Dst)) {
            $errTab[] = 'Image : Erreur de crop wide';
        }

        $img_Dst = 'min-'.$filename;

        if (!ImageManipulator::cropImage(198, 138, $targetDir.$filename, $targetDir.$img_Dst)) {
            $errTab[] = 'Image : Erreur de crop wide';
        }
    }
}

// envoi du résultat :
if (count($errTab) > 0) {
    $result = ['success' => 0, 'error' => implode(', ', $errTab)];
}

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
