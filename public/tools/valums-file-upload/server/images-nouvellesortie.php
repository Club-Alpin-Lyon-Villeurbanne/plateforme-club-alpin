<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define('DS', \DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__, 3).DS);				// Racine
include ROOT.'app'.DS.'includes.php';

$errTab = [];

// $errTab[]="Test";
if (!user()) {
    $errTab[] = 'User non connecté';
} elseif (!$_SESSION['user']['id_user']) {
    $errTab[] = 'ID manquant';
}

$mode = $_GET['mode'];
$id_evt = (int) ($_GET['id_evt']);

if ('edit' == $mode && !$id_evt) {
    $errTab[] = 'ID sortie manquant';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // modification de sortie
    if ('edit' == $mode) {
        $targetDir = 'ftp/sorties/'.$id_evt.'/';
    } // depuis la racine
    // création de sortie
    else {
        $targetDir = 'ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvellesortie';
    } // depuis la racine

    $targetDirRel = '../../../'.$targetDir; // chemin relatif
    if (!file_exists($targetDirRel)) {
        mkdir($targetDirRel);
    }
    $targetDir .= '/';
    $targetDirRel .= '/';

    // Handle file uploads via XMLHttpRequest
    include 'vfu.classes.php';

    // list of valid extensions, ex. array("jpeg", "xml", "bmp")
    $allowedExtensions = ['jpeg', 'jpg', 'png',
                               'JPEG', 'JPG', 'PNG', ];
    // max file size in bytes
    $sizeLimit = 2 * 1024 * 1024;

    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
    $result = $uploader->handleUpload($targetDir);

    if ($result['error']) {
        $errTab[] = $result['error'];
    }

    // dev
    // $result['targetDir']=$targetDir;
}

if (!isset($errTab) || 0 === count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = strtolower(formater($tmpfilename, 4));

    // si le nom formaté diffère de l'original
    if ($filename != $tmpfilename) {
        // debug : copie impossible si le nom de fichier est juste une variante de CASSE
        // donc dans ce cas on le RENOMME
        if ($filename == strtolower($tmpfilename)) {
            if (!rename($targetDirRel.$tmpfilename, $targetDirRel.$filename)) {
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
                $errTab[] = 'Erreur de copie de '.$targetDirRel.$result['filename']." \n vers ".$targetDir.$filename;
            }
        }
    }

    // redimensionnement des images
    if (!isset($errTab) || 0 === count($errTab)) {
        $size = getimagesize($targetDirRel.$filename);
        if ($size[0] > 590 || $size[1] > 400) {
            include APP.'redims.php';
            $W_max = 590;
            $H_max = 400;
            $rep_Dst = $targetDirRel;
            $img_Dst = $filename;
            $rep_Src = $targetDirRel;
            $img_Src = $filename;
            // redim 1
            if (!fctredimimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                $errTab[] = 'Image : Erreur de redim';
            }
        }
    }
}

// enregistrement BDD si c'est une modificatino d'evt
if ((!isset($errTab) || 0 === count($errTab)) && 'edit' == $mode) {
    include SCRIPTS.'connect_mysqli.php';

    // save
    $filename = $mysqli->real_escape_string($filename);
    $req = 'INSERT INTO '.$pbd."img(evt_img, ordre_img, user_img, fichier_img)
						VALUES($id_evt,    100,    ".(int) ($_SESSION['user']['id_user']).", '$filename')";
    $mysqli->query($req);

    // maj ordre
    $id_img = $mysqli->insert_id;
    $req = 'UPDATE  '.$pbd."img SET ordre_img` =  '$id_img' WHERE ".$pbd."img.id_img =$id_img ";
    $mysqli->query($req);

    $mysqli->close;

    $result['id'] = $id_img;
}

// envoi du résultat :
if (isset($errTab) && count($errTab) > 0) {
    $result = ['success' => 0, 'error' => implode(', ', $errTab)];
}

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);

/* *

$log.="\n  errTab :";
foreach($errTab as $key=>$value)
    $log.="\n $key = $value";


$fp = fopen('dev.txt', 'w');fwrite($fp, $log);fclose($fp);
/* */
