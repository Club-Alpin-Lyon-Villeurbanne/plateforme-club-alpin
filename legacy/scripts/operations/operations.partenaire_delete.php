<?php

use App\Legacy\LegacyContainer;

if (!admin()) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
    exit;
}
$uploaddir = __DIR__ . '/../../../public/ftp/partenaires/';

$part_id = (int) $_POST['part_id'];
$partenaireTab['part_image'] = trim($_POST['part_image']);

$req = "DELETE FROM `caf_partenaires` WHERE part_id='" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($part_id) . "'";

if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
    $errTab[] = 'Erreur SQL';
} elseif (LegacyContainer::get('legacy_mysqli_handler')->affectedRows() < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
} else {
    if (is_file($uploaddir . $partenaireTab['part_image'])) {
        // delete old file
        unlink($uploaddir . $partenaireTab['part_image']);
    }
}

exit;
