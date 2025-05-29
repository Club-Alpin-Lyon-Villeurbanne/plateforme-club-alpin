<?php

use App\Legacy\LegacyContainer;

$id_article = (int) $p2;
$status_article = 0;
$topubly_article = (isset($_POST['topubly_article']) && 'on' == $_POST['topubly_article'] ? 1 : 0);
$titre_article = stripslashes($_POST['titre_article']);
$commission_article = (int) $_POST['commission_article'];
$evt_article = (int) $_POST['evt_article'];
$une_article = (isset($_POST['une_article']) && 'on' == $_POST['une_article'] ? 1 : 0);
$cont_article = stripslashes($_POST['cont_article']);

// CHECKS
if ('' == $_POST['commission_article']) {
    $errTab[] = "Merci de sélectionner le type d'article";
}
// if(!$user_article) $errTab[]="ID User invalide";
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
/*
if(
    !file_exists('ftp/articles/'.$id_article.'/figure.jpg')
    or !file_exists('ftp/articles/'.$id_article.'/wide-figure.jpg')
    or !file_exists('ftp/articles/'.$id_article.'/min-figure.jpg')
     $errTab[] = "Les images liées sont introuvables";
     */

// enregistrement en BD
if (!isset($errTab) || 0 === count($errTab)) {
    $commission_article_value = $commission_article > 0 ? $commission_article : null;
    $evt_article_value = $evt_article ? $evt_article : null;
    $current_time = time();
    
    $sql = "UPDATE caf_article
    SET status_article = ?, topubly_article = ?, titre_article = ?, commission_article = ?, evt_article = ?, une_article = ?, cont_article = ?, tsp_article = ?
    WHERE id_article = ?";
    
    // on verifie si on est l'auteur que si on a pas le droit de modifier TOUS les articles
    if (!allowed('article_edit_notmine')) {
        $sql .= " AND user_article = ?";
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($sql);
        $user_id = getUser()->getId();
        $stmt->bind_param("iiisiisii", $status_article, $topubly_article, $titre_article, $commission_article_value, $evt_article_value, $une_article, $cont_article, $current_time, $id_article, $user_id);
    } else {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($sql);
        $stmt->bind_param("iiisiisii", $status_article, $topubly_article, $titre_article, $commission_article_value, $evt_article_value, $une_article, $cont_article, $current_time, $id_article);
    }
    
    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL';
    } elseif (LegacyContainer::get('legacy_mysqli_handler')->affectedRows() < 1) {
        $errTab[] = "Aucun enregistrement affecté : ID introuvable, ou vous n'êtes pas le créateur de cette article, ou bien aucune modification n'a été apportée.";
    }
    $stmt->close();
}

// debug : reload page
if (!isset($errTab) || 0 === count($errTab)) {
    header("Location: /article-edit/$id_article.html?lbxMsg=article_edit_success");
}
