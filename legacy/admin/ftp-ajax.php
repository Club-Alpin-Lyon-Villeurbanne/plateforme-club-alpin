<?php

use App\Ftp\FtpFile;
use App\Legacy\ImageManipulator;

require __DIR__.'/../app/includes.php';

$racine = '../ftp/';

$errTab = [];
$dirTab = [];
$fileTab = [];
$dossier = null;

if (!admin()) {
    $errTab[] = 'Votre session administrateur a expiré';
}

// vars et checks
if (0 === count($errTab)) {
    $dossier = $_GET['dossier'];
    // checks :
    if (substr($dossier, 0, strlen($racine)) != $racine || mb_substr_count($dossier, '../') > 1) {
        $errTab[] = "Le dossier demandé ($dossier) n'a pas le bon format.";
    }
    if (!file_exists($dossier)) {
        $errTab[] = 'Ce dossier est introuvable';
    }
}

// listage
if (0 === count($errTab)) {
    $one = false; // booleen : un dossier trouve au moins
    $opendir = opendir($dossier);
    while ($file = readdir($opendir)) {
        // c'est un dossier, non masqué
        if (is_dir($dossier.$file) && !FtpFile::shouldHide($file)) {
            $one = true;
            $dirTab[] = $file;
        }
        // c'est un fichier, non masqué
        if (!is_dir($dossier.$file) && !FtpFile::shouldHide($file)) {
            $one = true;
            $tmp = [];
            $tmp['name'] = $file;
            $tmp['filesize'] = filesize($dossier.$file);
            $tmp['filemtime'] = filemtime($dossier.$file);
            $tmp['filetype'] = filetype($dossier.$file);
            $tmp['ext'] = substr(strrchr($file, '.'), 1);
            $imgDim = ImageManipulator::getImageSize($dossier.$file);
            $tmp['imgw'] = (int) ($imgDim[0]);
            $tmp['imgh'] = (int) ($imgDim[1]);
            // $tmp['stat']=stat($dossier.$file);
            $fileTab[] = $tmp;
        }
    }
}

if (count($errTab) > 0) {
    $result['error'] = $errTab;
} else {
    $result['success'] = 1;
    $result['dirTab'] = $dirTab;
    $result['fileTab'] = $fileTab;
}
// affichage resultat
echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
