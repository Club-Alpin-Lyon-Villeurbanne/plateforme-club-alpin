<?php

use App\Legacy\LegacyContainer;

require __DIR__.'/../../app/includes.php';

$errTab = [];
$result = $targetDir = $filename = null;

// $errTab[]="Test";
if (!user()) {
    $errTab[] = 'User non connecté';
}

$mode = $_GET['mode'];
$id_evt = (int) ($_GET['id_evt']);

if ('edit' == $mode && !$id_evt) {
    $errTab[] = 'ID sortie manquant';
}

if (0 === count($errTab)) {
    // modification de sortie
    if ('edit' == $mode) {
        $targetDir = __DIR__.'/../../../public/ftp/sorties/'.$id_evt.'/';
    } // depuis la racine
    // création de sortie
    else {
        $targetDir = __DIR__.'/../../../public/ftp/user/'.getUser()->getIdUser().'/transit-nouvellesortie/';
    } // depuis la racine

    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir) && !is_dir($targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $targetDir));
        }
    }

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
        if ($size[0] > 590 || $size[1] > 400) {
            include __DIR__.'/../../app/redims.php';
            if (!resizeImage(590, 400, $targetDir.$filename, $targetDir.$filename)) {
                $errTab[] = 'Image : Erreur de redim';
            }
        }
    }
}

// enregistrement BDD si c'est une modificatino d'evt
if (0 === count($errTab) && 'edit' == $mode) {
    // save
    $filename = LegacyContainer::get('legacy_mysqli_handler')->escapeString($filename);
    $req = "INSERT INTO caf_img(evt_img, ordre_img, user_img, fichier_img)
						VALUES($id_evt,    100,    ".getUser()->getIdUser().", '$filename')";
    LegacyContainer::get('legacy_mysqli_handler')->query($req);

    // maj ordre
    $id_img = LegacyContainer::get('legacy_mysqli_handler')->insertId();
    $req = "UPDATE caf_img SET ordre_img` =  '$id_img' WHERE caf_img.id_img =$id_img ";
    LegacyContainer::get('legacy_mysqli_handler')->query($req);

    $result['id'] = $id_img;
}

// envoi du résultat :
if (count($errTab) > 0) {
    $result = ['success' => 0, 'error' => implode(', ', $errTab)];
}

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
