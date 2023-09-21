<?php

use App\Legacy\ImageManipulator;

require __DIR__.'/../../app/includes.php';

$errTab = [];
$result = $targetDir = $filename = null;

// $errTab[]="Test";
if (!user()) {
    $errTab[] = 'User non connecté';
}

$mode = $_GET['mode'] ?? '';
$id_article = (int) ($_GET['id_article'] ?? 0);

if ('edit' == $mode && !$id_article) {
    $errTab[] = 'ID sortie manquant';
}

if (0 === count($errTab)) {
    // creation des dossiers utiles pour l'user s'ils n'existnent pas
    $dir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId();
    if (!file_exists($dir)) {
        if (!mkdir($dir) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }
    $dir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle';
    if (!file_exists($dir)) {
        if (!mkdir($dir) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    // modification de sortie
    if ('edit' == $mode) {
        $targetDir = __DIR__.'/../../../public/ftp/articles/'.$id_article.'/';
    } // depuis la racine
    // création de sortie
    else {
        $targetDir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle/';
    } // depuis la racine

    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $targetDir));
        }
    }

    // Handle file uploads via XMLHttpRequest
    require __DIR__.'/vfu.classes.php';

    $uploader = new qqFileUploader();
    $result = $uploader->handleUpload($targetDir);

    if (isset($result['error']) && $result['error']) {
        $errTab[] = $result['error'];
    }
}

if (0 === count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = 'figure.jpg';

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
                // sauf erreur le nom de ficier est remplacé par sa version formatée
                $result['filename'] = $filename;
            } else {
                $errTab[] = 'Erreur de copie de '.$targetDir.$result['filename']." \n vers ".$targetDir.$filename;
            }
        }
    }

    if (0 === count($errTab)) {
        $img_Dst = 'wide-'.$filename;
        
        try {
            ImageManipulator::cropImage(665, 365, $targetDir.$filename, $targetDir.$img_Dst);
        } catch (\Exception $e) {
            $errTab[] = 'Erreur de crop wide : ' . $e->getMessage();
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
