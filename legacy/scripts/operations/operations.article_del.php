<?php

$id_article = (int) ($_POST['id_article']);

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
$req = "DELETE FROM caf_article WHERE id_article=$id_article AND status_article!=1 ";
if (allowed('article_delete_notmine')) {
    $req .= ' ';
} else {
    $req .= ' AND user_article='.(int) ($_SESSION['user']['id_user']);
}
if (!$mysqli->query($req)) {
    $errTab[] = 'Erreur SQL';
} elseif ($mysqli->affected_rows < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // suppression du dossier
    if ($id_article && is_dir('ftp/articles/'.$id_article)) {
        clearDir('ftp/articles/'.$id_article);
    }
}

$mysqli->close;

header('Location: /gestion-des-articles.html');
exit();
