<?php

$erreur = $code_ftp_user = $niveau_page = $ordre_page = null;

$nom_page = stripslashes($_POST['nom_page']);			// nom dans le menu
$titre_page = stripslashes($_POST['titre_page']);		// title
$description_page = stripslashes($_POST['description_page']);
$code_page = formater(stripslashes($_POST['nom_page']), 3);	// code (URL), ajouter boucle unique
$parent_page = (int) ($_POST['parent_page']);			// id parent, ou 0
$vis_page = 0; // par défaut

if (!$titre_page) {
    $erreur = "Merci d'entrer un titre";
}
if (!$nom_page) {
    $erreur = "Merci d'entrer un nom de page";
}

if (!$erreur) {
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    // creation code unique
    $suffixe = '';
    $doublon = true;
    for ($i = 1; $doublon; ++$i) {
        // Recherche sql
        $countSql = $mysqli->query("SELECT COUNT(*) FROM `caf_page` WHERE `code_page` LIKE '$code_page' LIMIT 1");
        if (!getArrayFirstValue($countSql->fetch_array(\MYSQLI_NUM))) {
            $doublon = false;
        }
    }
    $code_ftp_user .= $suffixe;

    // définition du niveau
    if ($parent_page) {
        $countSql = $mysqli->query("SELECT `niveau_page` FROM `caf_page` WHERE `id_page` =$parent_page LIMIT 1");
        $niveau_page = getArrayFirstValue($countSql->fetch_array(\MYSQLI_NUM)) + 1;
    }

    // adaptation
    $nom_page = $mysqli->real_escape_string($nom_page);
    $titre_page = $mysqli->real_escape_string($titre_page);
    $description_page = $mysqli->real_escape_string($description_page);

    // save
    $req = "INSERT INTO `caf_page` (`id_page` ,`ordre_page` ,`parent_page` ,`code_page` ,`nom_page` ,`niveau_page` ,`titre_page` ,`description_page` ,`vis_page`)
                                VALUES (NULL , '$ordre_page', '$parent_page', '$code_page', '$nom_page', '$niveau_page', '$titre_page', '$description_page', '$vis_page');";
    $mysqli->query($req);
}
