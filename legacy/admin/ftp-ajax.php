<?php

use App\Ftp\FtpFile;
use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

require __DIR__ . '/../app/includes.php';

$ftpPath = LegacyContainer::getParameter('legacy_ftp_path');

$errTab = [];
$dirTab = [];
$fileTab = [];
$dossier = null;

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    $errTab[] = 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
}

// vars et checks
if (0 === count($errTab)) {
    $dossier = !isset($_GET['dossier']) ? '' : urldecode($_GET['dossier']);
    if (!is_dir($ftpPath . $dossier)) {
        $errTab[] = 'Ce dossier est introuvable';
    }
}

// listage
if (0 === count($errTab)) {
    $package = new PathPackage('/ftp', new EmptyVersionStrategy());
    $dirPath = $dossier;
    $one = false; // booleen : un dossier trouve au moins
    $opendir = opendir($ftpPath . $dossier);
    while ($file = readdir($opendir)) {
        // c'est un dossier, non masqué
        if (is_dir($ftpPath . $dossier . $file) && !FtpFile::shouldHide($file)) {
            $one = true;
            $dirTab[] = $file;
        }
        // c'est un fichier, non masqué
        if (!is_dir($ftpPath . $dossier . $file) && !FtpFile::shouldHide($file)) {
            $one = true;
            $tmp = [];
            $tmp['name'] = $file;
            $tmp['path'] = $package->getUrl($dirPath . $file);
            $tmp['filesize'] = filesize($ftpPath . $dossier . $file);
            $tmp['filemtime'] = filemtime($ftpPath . $dossier . $file);
            $tmp['filetype'] = filetype($ftpPath . $dossier . $file);
            // $tmp['ext'] = substr(strrchr($file, '.'), 1);
            $pathInfo = pathinfo($ftpPath . $dossier . $file);
            $tmp['ext'] = $pathInfo['extension'];
            if (in_array($tmp['ext'], ['jpeg', 'jpg', 'gif', 'png', 'bmp', 'webp'], true)) {
                $imgDim = ImageManipulator::getImageSize($ftpPath . $dossier . $file);
                $tmp['imgw'] = (int) $imgDim[0];
                $tmp['imgh'] = (int) $imgDim[1];
            }
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
