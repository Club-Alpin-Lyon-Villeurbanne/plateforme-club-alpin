<?php

use App\Legacy\LegacyContainer;

$default_name_page = null;

$parent_page = (int) ($_POST['parent_page']);
$menu_page = (int) ($_POST['menu_page']);

$titreTab = $_POST['titre'];
if (!strlen($titreTab[0])) {
    $errTab[] = 'Nom de la page trop court pour cette langue : FR';
}
$menuTab = $_POST['menuname'];
if ($menu_page) {
    if (!strlen($menuTab[0])) {
        $errTab[] = 'Intitulé dans le menu trop court pour cette langue : FR';
    }
}
$code_page = strtolower(trim(LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['code_page']))));
// $default_name_page=trim(LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['default_name_page'])));
$meta_title_page = (int) ($_POST['meta_title_page']);
$priority_page = (int) ($_POST['priority_page']) / 100;
// $add_css_page=trim(LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['add_css_page'])));
// $add_js_page=trim(LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['add_js_page'])));
$add_js_page = $add_css_page = '';

// checks
$pattern = '#^[a-z0-9-]+$#';
if (!strlen($code_page)) {
    $errTab[] = 'Code de page trop court';
}
if (!preg_match($pattern, $code_page)) {
    $errTab[] = 'Le code de la page ne respecte pas le format demandé : chiffres, lettres sans accents, et tirets.';
}
// if(!strlen($default_name_page))		$errTab[]="Nom par défaut de page trop court. Si le nom est géré en multilangue, le nom entré ici sera invisible aux visiteusr mais permettra à l'administrateur de s'y repérer dans le back-office.";

$req = "SELECT COUNT(*) FROM `caf_page` WHERE `code_page` LIKE '$code_page' LIMIT 1";
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
if (getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM))) {
    $errTab[] = 'Ce code est déjà utilisé, veuillez en entrer un autre';
}

// save page
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "INSERT INTO `caf_page` (`id_page` ,`parent_page` ,`admin_page` ,`superadmin_page` ,`vis_page` ,`ordre_page` ,`menu_page` ,`ordre_menu_page` ,`menuadmin_page` ,`ordre_menuadmin_page` ,`code_page` ,`default_name_page` ,`meta_title_page` ,`meta_description_page` ,`priority_page` ,`add_css_page` ,`add_js_page` ,`lock_page`)
                                        VALUES (NULL , '$parent_page', '0', '0', '0', '0', '$menu_page', '0', '0', '0', '$code_page', '$default_name_page', '$meta_title_page', '0', '$priority_page', '$add_css_page', '$add_js_page', '0');";
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
    $contenu_content_inline = LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($titreTab[0]));
    $req = "INSERT INTO `caf_content_inline` (`id_content_inline` ,`groupe_content_inline` ,`code_content_inline` ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`)
                                        VALUES (NULL , '2', 'meta-title-$code_page', '$lang_content_inline', '$contenu_content_inline', '".time()."', '');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur BDD titles';
    }
}
// save menu
if ((!isset($errTab) || 0 === count($errTab)) && $menu_page) {
    $lang_content_inline = 'fr';
    $contenu_content_inline = LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($titreTab[0]));
    $req = "INSERT INTO `caf_content_inline` (`id_content_inline` ,`groupe_content_inline` ,`code_content_inline` ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`)
                                        VALUES (NULL , '4', 'mainmenu-$code_page', '$lang_content_inline', '$contenu_content_inline', '".time()."', '');";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur BDD titles';
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    mylog('page-create', "Création de la page $default_name_page ($code_page)");
}
