<?php

use App\Legacy\LegacyContainer;

$status_article = 0;
$topubly_article = ('on' == $_POST['topubly_article'] ? 1 : 0);
$tsp_crea_article = time();
$tsp_article = time();
$user_article = getUser()->getId();
$titre_article = stripslashes($_POST['titre_article']);
$code_article = substr(formater($titre_article, 3), 0, 30);
$commission_article = (int) ($_POST['commission_article']);
$evt_article = (int) ($_POST['evt_article']);
$une_article = ('on' == $_POST['une_article'] ? 1 : 0);
$cont_article = stripslashes($_POST['cont_article']);
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

if (
    !LegacyContainer::get('legacy_fs')->exists(__DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle/figure.jpg')
    || !LegacyContainer::get('legacy_fs')->exists(__DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle/wide-figure.jpg')
    || !LegacyContainer::get('legacy_fs')->exists(__DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle/min-figure.jpg')
    ) {
    $errTab[] = 'Les images liées sont introuvables';
}

// enregistrement en BD
if (!isset($errTab) || 0 === count($errTab)) {
    $titre_article = LegacyContainer::get('legacy_mysqli_handler')->escapeString($titre_article);
    $code_article = LegacyContainer::get('legacy_mysqli_handler')->escapeString($code_article);
    $cont_article = LegacyContainer::get('legacy_mysqli_handler')->escapeString($cont_article);

    $req = "INSERT INTO caf_article(`status_article` ,`topubly_article` ,`tsp_crea_article` ,`tsp_article` ,`user_article` ,`titre_article` ,`code_article` ,`commission_article` ,`evt_article` ,`une_article` ,`cont_article`)
                        VALUES ('$status_article',  '$topubly_article',  '$tsp_crea_article',  '$tsp_article',  '$user_article',  '$titre_article',  '$code_article', ".($commission_article > 0 ? "'$commission_article'" : 'null').",  '$evt_article',  '$une_article',  '$cont_article');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    } else {
        $id_article = LegacyContainer::get('legacy_mysqli_handler')->insertId();
    }
}

// déplacement des fichiers
if ((!isset($errTab) || 0 === count($errTab)) && $id_article > 0) {
    $dirFrom = __DIR__.'/../../../public/ftp/user/'.getUser()->getId().'/transit-nouvelarticle/';
    $dirTo = __DIR__.'/../../../public/ftp/articles/'.$id_article.'/';

    LegacyContainer::get('legacy_fs')->mkdir($dirTo);
    LegacyContainer::get('legacy_fs')->copy($dirFrom.'figure.jpg', $dirTo.'figure.jpg');
    LegacyContainer::get('legacy_fs')->copy($dirFrom.'min-figure.jpg', $dirTo.'min-figure.jpg');
    LegacyContainer::get('legacy_fs')->copy($dirFrom.'wide-figure.jpg', $dirTo.'wide-figure.jpg');
    LegacyContainer::get('legacy_fs')->remove([
        $dirFrom.'figure.jpg',
        $dirFrom.'min-figure.jpg',
        $dirFrom.'wide-figure.jpg',
    ]);
}

if (!isset($errTab) || 0 === count($errTab)) {
    header('Location: profil/articles.html?lbxMsg=article_create_success');
}
