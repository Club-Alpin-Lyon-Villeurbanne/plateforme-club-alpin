<?php

$id_article = (int) ($_POST['id_article']);

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

$req = "UPDATE caf_article SET topubly_article=0, status_article=0, tsp_validate_article=0 WHERE id_article=$id_article";
if (!allowed('article_edit_notmine')) {
    $req .= ' AND user_article='.(int) ($_SESSION['user']['id_user']);
}

if (!$mysqli->query($req)) {
    $errTab[] = 'Erreur SQL:'.$mysqli->error;
} elseif ($mysqli->affected_rows < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
}

$mysqli->close;

header('Location: /gestion-des-articles.html');
exit();
