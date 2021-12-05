<?php

global $kernel;

if (!admin()) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
    exit;
}
$uploaddir = __DIR__.'/../../../public/ftp/partenaires/';

$part_id = (int) ($_POST['part_id']);
$partenaireTab['part_image'] = trim($_POST['part_image']);

$req = "DELETE FROM `caf_partenaires` WHERE part_id='".$kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($part_id)."'";

if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
    $errTab[] = 'Erreur SQL';
} elseif ($kernel->getContainer()->get('legacy_mysqli_handler')->affectedRows() < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
} else {
    if (is_file($uploaddir.$partenaireTab['part_image'])) {
        //delete old file
        unlink($uploaddir.$partenaireTab['part_image']);
    }
}

exit();
