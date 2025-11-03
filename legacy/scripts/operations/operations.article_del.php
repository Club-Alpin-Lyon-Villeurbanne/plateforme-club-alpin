<?php

use App\Legacy\LegacyContainer;

$id_article = (int) $_POST['id_article'];

$req = "DELETE FROM caf_article WHERE id_article=$id_article AND status_article!=1 ";
if (allowed('article_delete_notmine')) {
    $req .= ' ';
} else {
    $req .= ' AND user_article=' . getUser()->getId();
}
if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
    $errTab[] = 'Erreur SQL';
} elseif (LegacyContainer::get('legacy_mysqli_handler')->affectedRows() < 1) {
    $errTab[] = 'Aucun enregistrement affecté';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // suppression du dossier
    if ($id_article && is_dir(__DIR__ . '/../../../public/ftp/articles/' . $id_article)) {
        clearDir(__DIR__ . '/../../../public/ftp/articles/' . $id_article);
    }
}

header('Location: /profil/articles.html');
exit;
