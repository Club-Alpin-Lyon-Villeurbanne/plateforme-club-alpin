<?php

use App\Legacy\LegacyContainer;

$status_article = 0;
$topubly_article = (isset($_POST['topubly_article']) && 'on' == $_POST['topubly_article'] ? 1 : 0);
$tsp_crea_article = time();
$tsp_article = time();
$user_article = getUser()->getId();
$titre_article = stripslashes(isset($_POST['titre_article']) ? $_POST['titre_article'] : '');
$code_article = substr(formater($titre_article, 3), 0, 30);
$commission_article = (int) $_POST['commission_article'];
$evt_article = (int) $_POST['evt_article'];
$une_article = (isset($_POST['une_article']) && 'on' == $_POST['une_article'] ? 1 : 0);
$cont_article = stripslashes(isset($_POST['cont_article']) ? $_POST['cont_article'] : '');
$id_article = null;

// CHECKS
if ('' == $_POST['commission_article']) {
    $errTab[] = "Merci de sélectionner le type d'article";
}
if (!$user_article) {
    $errTab[] = 'ID User invalide';
}
if (strlen($titre_article) < 3) {
    $errTab[] = 'Merci de rentrer un titre valide';
}
if (strlen($titre_article) > 200) {
    $errTab[] = 'Merci de rentrer un titre inférieur à 200 caractères';
}
if (-1 == $commission_article && !$evt_article) {
    $errTab[] = 'Si cet article est un compte rendu de sortie, veuillez sélectionner la sortie liée.';
}
if (strlen($cont_article) < 10) {
    $errTab[] = 'Merci de rentrer un contenu valide';
}
// image
if (
    !file_exists(__DIR__ . '/../../../public/ftp/user/' . getUser()->getId() . '/transit-nouvelarticle/figure.jpg')
    || !file_exists(__DIR__ . '/../../../public/ftp/user/' . getUser()->getId() . '/transit-nouvelarticle/wide-figure.jpg')
    || !file_exists(__DIR__ . '/../../../public/ftp/user/' . getUser()->getId() . '/transit-nouvelarticle/min-figure.jpg')
) {
    $errTab[] = 'Merci de rajouter une photo à l\'article';
}

// enregistrement en BD
if (!isset($errTab) || 0 === count($errTab)) {
    $commission_article_value = $commission_article > 0 ? $commission_article : null;
    $evt_article_value = $evt_article ?: null;

    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO caf_article(`status_article` ,`topubly_article` ,`tsp_crea_article` ,`tsp_article` ,`user_article` ,`titre_article` ,`code_article` ,`commission_article` ,`evt_article` ,`une_article` ,`cont_article`)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iiiiissiiis', $status_article, $topubly_article, $tsp_crea_article, $tsp_article, $user_article, $titre_article, $code_article, $commission_article_value, $evt_article_value, $une_article, $cont_article);
    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL';
    } else {
        $id_article = LegacyContainer::get('legacy_mysqli_handler')->insertId();
    }
    $stmt->close();
}

// déplacement des fichiers
if ((!isset($errTab) || 0 === count($errTab)) && $id_article > 0) {
    // repertoire de l'image a recuperer
    $dirFrom = __DIR__ . '/../../../public/ftp/user/' . getUser()->getId() . '/transit-nouvelarticle/';
    // créa du repertroie destination
    $dirTo = __DIR__ . '/../../../public/ftp/articles/' . $id_article;
    if (!file_exists($dirTo)) {
        if (!mkdir($dirTo, 0755, true) && !is_dir($dirTo)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dirTo));
        }
    }
    $dirTo .= '/';

    // copie & suppression
    if (copy($dirFrom . 'figure.jpg', $dirTo . 'figure.jpg')) {
        unlink($dirFrom . 'figure.jpg');
    }
    if (copy($dirFrom . 'min-figure.jpg', $dirTo . 'min-figure.jpg')) {
        unlink($dirFrom . 'min-figure.jpg');
    }
    if (copy($dirFrom . 'wide-figure.jpg', $dirTo . 'wide-figure.jpg')) {
        unlink($dirFrom . 'wide-figure.jpg');
    }
}

// redirecion
if (!isset($errTab) || 0 === count($errTab)) {
    header('Location: profil/articles.html?lbxMsg=article_create_success');
}
