<?php

use App\Legacy\LegacyContainer;

$nom_content_inline_group = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim(stripslashes($_POST['nom_content_inline_group'])));

// checks
if (!strlen($nom_content_inline_group)) {
    $errTab[] = 'Entrez un nom';
}
$req = "SELECT COUNT(*) FROM caf_content_inline_group WHERE nom_content_inline_group LIKE '$nom_content_inline_group' ";
$handleCount = LegacyContainer::get('legacy_mysqli_handler')->query($req);
if (getArrayFirstValue($handleCount->fetch_array(\MYSQLI_NUM))) {
    $errTab[] = 'Erreur : ce groupe existe déjà dans la liste';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $nom_content_inline_group = LegacyContainer::get('legacy_mysqli_handler')->escapeString($nom_content_inline_group);

    $req = "INSERT INTO `caf_content_inline_group` (`ordre_content_inline_group` ,`nom_content_inline_group`)
                                                    VALUES ('', '$nom_content_inline_group');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $erreur = 'Erreur BDD<br />'.$req;
    }
    $id_content_inline_group = LegacyContainer::get('legacy_mysqli_handler')->insertId();
    $req = "UPDATE `caf_content_inline_group` SET `ordre_content_inline_group` = '$id_content_inline_group' WHERE `caf_content_inline_group`.`id_content_inline_group` =$id_content_inline_group LIMIT 1 ;";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $erreur = 'Erreur BDD<br />'.$req;
    }
}
