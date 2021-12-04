<?php

global $kernel;

if (!admin()) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
    exit;
}
$uploaddir = __DIR__.'/../../../public/ftp/partenaires/';
$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

$part_id = (int) ($_POST['part_id']);
$partenaireTab['part_image'] = trim($_POST['part_image']);

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
$req = "DELETE FROM `caf_partenaires` WHERE part_id='".$mysqli->real_escape_string($part_id)."'";

if (!$mysqli->query($req)) {
    $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
        'error' => $mysqli->error,
        'file' => __FILE__,
        'line' => __LINE__,
        'sql' => $req,
    ]);
    $errTab[] = 'Erreur SQL';
} elseif ($mysqli->affected_rows < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
} else {
    if (is_file($uploaddir.$partenaireTab['part_image'])) {
        //delete old file
        unlink($uploaddir.$partenaireTab['part_image']);
    }
}

exit();
