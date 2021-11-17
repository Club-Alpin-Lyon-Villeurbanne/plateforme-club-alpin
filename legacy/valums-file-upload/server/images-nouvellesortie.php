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
        $targetDir = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvellesortie/';
    } // depuis la racine

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
    $filename = strtolower(formater($tmpfilename, 4));

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
        if (!ImageManipulator::resizeImage(590, 400, $targetDir.$filename, $targetDir.$filename, true)) {
            $errTab[] = 'Image : Erreur de redim';
        }
    }
}

// enregistrement BDD si c'est une modificatino d'evt
if (0 === count($errTab) && 'edit' == $mode) {
    // save
    $filename = LegacyContainer::get('legacy_mysqli_handler')->escapeString($filename);
    $req = "INSERT INTO caf_img(evt_img, ordre_img, user_img, fichier_img)
						VALUES($id_evt,    100,    ".getUser()->getId().", '$filename')";
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
