<?php

use App\Legacy\LegacyContainer;

$id_article = (int) $_POST['id_article'];

$req = "UPDATE caf_article SET topubly_article=0, status_article=0, tsp_validate_article=0 WHERE id_article=$id_article";
if (!allowed('article_edit_notmine')) {
    $req .= ' AND user_article=' . getUser()->getId();
}

if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
    $errTab[] = 'Erreur SQL';
} elseif (LegacyContainer::get('legacy_mysqli_handler')->affectedRows() < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
}

header('Location: /gestion-des-articles.html');
exit;
