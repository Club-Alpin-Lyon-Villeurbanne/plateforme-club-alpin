<?php

use App\Legacy\LegacyContainer;

$parent_page = 54; /* ◊◊◊◊◊ IMPORTANT : doit être l'ID de l'entrée 'pages' dans la BDD ◊◊◊◊◊ */
$default_name_page = LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['default_name_page']));
$default_description_page = LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['default_description_page']));
$code_page = strtolower(trim(LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['code_page']))));
$priority_page = (int) ($_POST['priority_page']) / 10;

// meta description par defaut, ou sur-mesure ?
if ($default_description_page) {
    $meta_description_page = 1;
} // sur mesure
else {
    $meta_description_page = 0;
} // par defaut (celle du site)

// checks
$pattern = '#^[a-z0-9-]+$#';
if (!strlen($code_page)) {
    $errTab[] = 'Code de page trop court';
}
if (!preg_match($pattern, $code_page)) {
    $errTab[] = 'Le code de la page ne respecte pas le format demandé : chiffres, lettres sans accents, et tirets.';
}

$req = "SELECT COUNT(id_page) FROM `caf_page` WHERE `code_page` LIKE '$code_page' LIMIT 1";
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
if (getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM))) {
    $errTab[] = 'Ce code est déjà utilisé par une autre page, veuillez le modifier pour le rendre unique.';
}

// save page
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "INSERT INTO caf_page (id_page ,parent_page ,admin_page ,superadmin_page ,vis_page ,ordre_page ,menu_page ,ordre_menu_page ,menuadmin_page ,ordre_menuadmin_page ,code_page ,default_name_page ,meta_title_page ,meta_description_page ,priority_page ,add_css_page ,add_js_page ,lock_page ,pagelibre_page ,created_page)
                        VALUES (NULL , '$parent_page', '0', '0', '0', '', '', '', '', '', '$code_page', '$default_name_page', '0', '$meta_description_page', '$priority_page', '', '', '', '1', '".time()."');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur BDD 1';
    } else {
        $id_page = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        $req = "UPDATE `caf_page` SET `ordre_page`= '$id_page', `ordre_menu_page`= '$id_page' WHERE `caf_page`.`id_page` =$id_page LIMIT 1 ;";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur BDD 2';
        }
    }
}
// save titles
if (!isset($errTab) || 0 === count($errTab)) {
    $lang_content_inline = 'fr';
    $contenu_content_inline = $default_name_page;
    $req = "INSERT INTO `caf_content_inline` (`id_content_inline` ,`groupe_content_inline` ,`code_content_inline` ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`)
                                        VALUES (NULL , '2', 'meta-title-$code_page', '$lang_content_inline', '$contenu_content_inline', '".time()."', '');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur BDD title';
    }
}
// save description // si necessaire
if ((!isset($errTab) || 0 === count($errTab)) && $default_description_page) {
    $lang_content_inline = 'fr';
    $contenu_content_inline = $default_description_page;
    $req = "INSERT INTO `caf_content_inline` (`id_content_inline` ,`groupe_content_inline` ,`code_content_inline` ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`)
                                        VALUES (NULL , '2', 'meta-description-$code_page', '$lang_content_inline', '$contenu_content_inline', '".time()."', '');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur BDD title';
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    mylog('page-libre-create', "Création de la page libre $default_name_page ($code_page)");
}
