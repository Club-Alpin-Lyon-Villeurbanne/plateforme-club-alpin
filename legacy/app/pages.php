<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// ___________________ CONFIGURATION DES PAGES
// ___________________ Cette version déclare une variable contenant toutes les specs de toutes
// ___________________ les pages, ce qui est lourd pour le serveur mais pratique pour la gestion des menus

// defaut
$p_defpage = 'accueil';
if (!$p1) {
    $p1 = $p_defpage;
}

// options des pages
cont(false); // initialisation des contenus

$p_pages = [];

// On ne requiert que les pages necessaires en fonction du mode admin et superadmin
$req = 'SELECT * FROM  `caf_page` '
        . 'WHERE 1 '
        . (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER) ? '' : ' AND vis_page=1 ') // les admins ont le droit de voir les pages cachées
        . (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER) ? '' : ' AND admin_page=0 ') // seuls les admin peuvent voir les pages admin
        . (isGranted(SecurityConstants::ROLE_ADMIN) ? '' : ' AND superadmin_page=0 ') // seuls les superadmin peuvent voir les pages superadmin
        . 'ORDER BY ordre_menu_page ASC, ordre_menuadmin_page ASC' // on sort tout de suite dans l'ordre des menus
;
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    $p_pages[$handle['code_page']] = [
        // 'meta_title_page'=>$handle['meta_title_page']?cont('meta-title-'.$handle['code_page']):cont('meta-title-site'),
        'meta_title_page' => $handle['meta_title_page'] ? $handle['default_name_page'] : cont('meta-title-' . $handle['code_page']),
        'meta_description_page' => $handle['meta_description_page'] ? cont('meta-description-' . $handle['code_page']) : cont('site-meta-description'),
        'vis_page' => $handle['vis_page'],
        'menu_page' => $handle['menu_page'],
        'menuadmin_page' => $handle['menuadmin_page'],
        'default_name_page' => $handle['default_name_page'],
        'admin_page' => $handle['admin_page'] ? true : false,
        'superadmin_page' => $handle['superadmin_page'] ? true : false,
        'parent_page' => $handle['parent_page'],
        'id_page' => $handle['id_page'],
        'add_js_page' => $handle['add_js_page'],
        'add_css_page' => $handle['add_css_page'],
    ];
}

$codePrioritaire = null;

// DEFINITION DES VARS UTILISEES SUR LA PAGE
if ($p_pages[$p2] ?? null) {
    $codePrioritaire = $p2;
} elseif ($p_pages[$p1]) {
    $codePrioritaire = $p1;
}

// Les pages d'accueil commission sont prioritaires
if ('accueil' == $p1) {
    $codePrioritaire = $p1;
}
if ('creer-une-sortie' == $p1) {
    $codePrioritaire = $p1;
}

// PAGE TROUVEE
if ($p_pages[$codePrioritaire] ?? null) {
    // page trouvée, mais si pas de sous-page précisée alors qu'une existe, on redirige vers la première sous-page
    // Dépends de la navigation sur le siten retirer le bloc ci-dessous si necessaire
    if (!$p2) {
        foreach ($p_pages as $code => $page) {
            if ($page['parent_page'] == $p_pages[$p1]['id_page']) {
                header('Location: ' . $p1 . '/' . $code . '.html');
                exit;
            }
        }
    }
    // sinon, récup infos
    $currentPage1 = $p_pages[$p1] ?? null; // toutes les infos de la page courante
    $currentPage2 = $p_pages[$p2] ?? null; // toutes les infos de la page courante
    $currentPage3 = $p_pages[$p3] ?? null; // toutes les infos de la page courante
    $meta_title = $p_pages[$codePrioritaire]['meta_title_page'] ?: cont('site-meta-title');
    $meta_description = $p_pages[$codePrioritaire]['meta_description_page'] ?: cont('site-meta-description');
    $p_pageadmin = $p_pages[$codePrioritaire]['admin_page'] ? true : false; // gère le style de la page
    $p_addJs = explode(';', $p_pages[$codePrioritaire]['add_js_page']);
    $p_addCss = explode(';', $p_pages[$codePrioritaire]['add_css_page']);
}
// PAS TROUVE
else {
    throw new NotFoundHttpException();
}
