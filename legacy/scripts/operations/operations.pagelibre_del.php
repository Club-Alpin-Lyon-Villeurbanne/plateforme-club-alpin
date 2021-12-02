<?php

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
$id_page = (int) ($_POST['id_page']);

if (!$id_page) {
    $errTab[] = 'ID manquant';
}
if ('SUPPRIMER' != $_POST['confirm']) {
    $errTab[] = 'Vous devez recopier le texte approprié pour confirmer la suppression.';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $req = "DELETE FROM caf_page WHERE id_page=$id_page LIMIT 1";
    if (!$mysqli->query($req)) {
        $erreur = 'Erreur BDD<br />'.$req;
    }

    $req = "DELETE FROM caf_content_html WHERE code_content_html LIKE 'pagelibre-$id_page'";
    if (!$mysqli->query($req)) {
        $erreur = 'Erreur BDD2<br />'.$req;
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    mylog('pagelibre-delete', "Suppression de la page libre id=$id_page");
}
