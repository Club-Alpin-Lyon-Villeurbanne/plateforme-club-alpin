<?php

use App\Legacy\LegacyContainer;

$id_article = (int) $_POST['id_article'];

$req = "UPDATE caf_article SET updated_at = NOW() WHERE caf_article.id_article=$id_article"; // premiere validation

if (!allowed('article_validate_all')) {
    $req .= ' AND user_article=' . getUser()->getId();
}

if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
    $errTab[] = 'Aucun enregistrement affecté';
}
