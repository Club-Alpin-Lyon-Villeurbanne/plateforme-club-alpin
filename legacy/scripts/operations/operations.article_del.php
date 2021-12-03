<?php

global $kernel;

$id_article = (int) ($_POST['id_article']);

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
$req = "DELETE FROM caf_article WHERE id_article=$id_article AND status_article!=1 ";
if (allowed('article_delete_notmine')) {
    $req .= ' ';
} else {
    $req .= ' AND user_article='.getUser()->getIdUser();
}
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
}

if (!isset($errTab) || 0 === count($errTab)) {
    // suppression du dossier
    if ($id_article && is_dir(__DIR__.'/../../../public/ftp/articles/'.$id_article)) {
        clearDir(__DIR__.'/../../../public/ftp/articles/'.$id_article);
    }
}

$mysqli->close;

header('Location: /gestion-des-articles.html');
exit();
