<?php

global $kernel;

$id_article = (int) ($_POST['id_article']);

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

$req = 'UPDATE caf_article SET tsp_validate_article='.time()." WHERE caf_article.id_article=$id_article"; // premiere validation

if (!allowed('article_validate_all')) {
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

$mysqli->close;
