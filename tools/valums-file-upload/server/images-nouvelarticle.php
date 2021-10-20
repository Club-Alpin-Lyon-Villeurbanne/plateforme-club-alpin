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
$id_article = (int) ($_GET['id_article']);

if ('edit' == $mode && !$id_article) {
    $errTab[] = 'ID sortie manquant';
}

if (!count($errTab)) {
    // creation des dossiers utiles pour l'user s'ils n'existnent pas
    $dir = '../../../ftp/user/'.(int) ($_SESSION['user']['id_user']);
    if (!file_exists($dir)) {
        mkdir($dir);
    }
    $dir = '../../../ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvelarticle';
    if (!file_exists($dir)) {
        mkdir($dir);
    }

    // modification de sortie
    if ('edit' == $mode) {
        $targetDir = 'ftp/articles/'.$id_article;
    } // depuis la racine
    // création de sortie
    else {
        $targetDir = 'ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvelarticle';
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
    $sizeLimit = 5 * 1024 * 1024;

    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
    $result = $uploader->handleUpload($targetDir);

    if ($result['error']) {
        $errTab[] = $result['error'];
    }

    // dev
    // $result['targetDir']=$targetDir;
}

if (!count($errTab)) {
    $tmpfilename = $result['filename'];
    $filename = 'figure.jpg';

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
    if (!count($errTab)) {
        include APP.'redims.php';
        $size = getimagesize($targetDirRel.$filename);

        // 1 : WIDE = l'image qui prend la largeur de la page dédiée à l'article / +dans le slider de la home
        // plus large que haute, proportionnellement aux dimensions voulues ?
        if ($size[0] / 665 > $size[1] / 365) {
            $W_max = 0;
            $H_max = 365;
        } // alors redimensionne en hauteur pour cropper les bords latéraux ensuite
        else {
            $W_max = 665;
            $H_max = 0;
        } // sinon l'inverse : on crope les bords haut & bas
        // redimension
        $rep_Dst = $targetDirRel;
        $img_Dst = 'wide-'.$filename;
        $rep_Src = $targetDirRel;
        $img_Src = $filename;
        if (!fctredimimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
            $errTab[] = 'Image : Erreur de redim wide';
        }
        // crop
        $W_max = 665;
        $H_max = 365;
        $img_Dst = 'wide-'.$filename;
        $img_Src = 'wide-'.$filename;
        if (!fctcropimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
            $errTab[] = 'Image : Erreur de crop wide';
        }

        // 2 : MIN = affichée dans les listes d'articles
        // plus large que haute, proportionnellement aux dimensions voulues ?
        if ($size[0] / 198 > $size[1] / 138) {
            $W_max = 0;
            $H_max = 138;
        } // alors redimensionne en hauteur pour cropper les bords latéraux ensuite
        else {
            $W_max = 198;
            $H_max = 0;
        } // sinon l'inverse : on crope les bords haut & bas
        // redimension
        $rep_Dst = $targetDirRel;
        $img_Dst = 'min-'.$filename;
        $rep_Src = $targetDirRel;
        $img_Src = $filename;
        if (!fctredimimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
            $errTab[] = 'Image : Erreur de redim wide';
        }
        // crop
        $W_max = 198;
        $H_max = 138;
        $img_Dst = 'min-'.$filename;
        $img_Src = 'min-'.$filename;
        if (!fctcropimage($W_max, $H_max, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
            $errTab[] = 'Image : Erreur de crop wide';
        }
    }
}

// envoi du résultat :
if (count($errTab)) {
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
