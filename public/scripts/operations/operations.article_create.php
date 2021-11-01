<?php

$status_article = 0;
$topubly_article = ('on' == $_POST['topubly_article'] ? 1 : 0);
$tsp_crea_article = $p_time;
$tsp_article = $p_time;
$user_article = (int) ($_SESSION['user']['id_user']);
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
// image
if (
    !file_exists('ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvelarticle/figure.jpg')
    || !file_exists('ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvelarticle/wide-figure.jpg')
    || !file_exists('ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvelarticle/min-figure.jpg')
    ) {
    $errTab[] = 'Les images liées sont introuvables';
}

// enregistrement en BD
if (!isset($errTab) || 0 === count($errTab)) {
    include SCRIPTS.'connect_mysqli.php';
    $titre_article = $mysqli->real_escape_string($titre_article);
    $code_article = $mysqli->real_escape_string($code_article);
    $cont_article = $mysqli->real_escape_string($cont_article);

    $req = "INSERT INTO caf_article(`id_article` ,`status_article` ,`topubly_article` ,`tsp_crea_article` ,`tsp_article` ,`user_article` ,`titre_article` ,`code_article` ,`commission_article` ,`evt_article` ,`une_article` ,`cont_article`)
                        VALUES (NULL ,  '$status_article',  '$topubly_article',  '$tsp_crea_article',  '$tsp_article',  '$user_article',  '$titre_article',  '$code_article',  '$commission_article',  '$evt_article',  '$une_article',  '$cont_article');";
    if (!$mysqli->query($req)) {
        $errTab[] = 'Erreur SQL';
    } else {
        $id_article = $mysqli->insert_id;
    }
    $mysqli->close;
}

// déplacement des fichiers
if ((!isset($errTab) || 0 === count($errTab)) && $id_article > 0) {
    // repertoire de l'image a recuperer
    $dirFrom = 'ftp/user/'.(int) ($_SESSION['user']['id_user']).'/transit-nouvelarticle/';
    // créa du repertroie destination
    $dirTo = 'ftp/articles/'.$id_article;
    if (!file_exists($dirTo)) {
        mkdir($dirTo);
    }
    $dirTo .= '/';

    // copie & suppression
    if (copy($dirFrom.'figure.jpg', $dirTo.'figure.jpg')) {
        unlink($dirFrom.'figure.jpg');
    }
    if (copy($dirFrom.'min-figure.jpg', $dirTo.'min-figure.jpg')) {
        unlink($dirFrom.'min-figure.jpg');
    }
    if (copy($dirFrom.'wide-figure.jpg', $dirTo.'wide-figure.jpg')) {
        unlink($dirFrom.'wide-figure.jpg');
    }
}

// redirecion
if (!isset($errTab) || 0 === count($errTab)) {
    header('Location: profil/articles.html?lbxMsg=article_create_success');
}
