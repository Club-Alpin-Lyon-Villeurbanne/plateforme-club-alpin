<?php

global $kernel;

$id_article = (int) ($_POST['id_article']);

$req = 'UPDATE caf_article SET tsp_validate_article='.time()." WHERE caf_article.id_article=$id_article"; // premiere validation

if (!allowed('article_validate_all')) {
    $req .= ' AND user_article='.getUser()->getIdUser();
}

if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
    $errTab[] = 'Erreur SQL';
} elseif ($kernel->getContainer()->get('legacy_mysqli_handler')->affectedRows() < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
}
