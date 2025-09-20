<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

require __DIR__ . '/app/includes.php';

// ________________________________________________ TRAITEMENT AJAX
if (isset($_GET['ajx']) && !strpos($_GET['ajx'], '../')) {
    require __DIR__ . '/app/ajax/' . $_GET['ajx'] . '.php';
    exit;
}

// lien vers cette page (pour formulaires, ou ancres)
$versCettePage = $p1 . ($p2 ? '/' . $p2 : '') . ($p3 ? '/' . $p3 : '') . ($p4 ? '/' . $p4 : '') . '.html';			// multilangue / une langue
$eventTitle = '';
$eventTimestamp = 0;
if (!empty($evt) && is_array($evt) && array_key_exists('titre_evt', $evt) && !empty($evt['titre_evt'])) {
    $eventTitle = $evt['titre_evt'];
} elseif (!empty($_GET['titre_evt'])) {
    $eventTitle = $_GET['titre_evt'];
}
if (!empty($evt) && is_array($evt) && array_key_exists('tsp_evt', $evt) && !empty($evt['tsp_evt'])) {
    $eventTimestamp = $evt['tsp_evt'];
} elseif (!empty($_GET['tsp_evt'])) {
    $eventTimestamp = $_GET['tsp_evt'];
}

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>
    <?php echo html_utf8($meta_title); ?>
    <?php if ('feuille-de-sortie' == $p1) { ?>
        - Feuille de sortie - <?php echo html_utf8($eventTitle); ?> - <?php echo date('d.m.Y', $eventTimestamp); ?>
    <?php } ?>
    </title>
    <base href="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>" />
    <meta name="description" content="<?php echo html_utf8($meta_description); ?>">
    <meta name="author" content="<?php echo LegacyContainer::getParameter('legacy_env_SITENAME'); ?>">
    <meta name="viewport" content="width=1000">
    <?php
    if (LegacyContainer::getParameter('legacy_env_GOOGLE_SITE_VERIFICATION')) {
        ?>
        <meta name="google-site-verification" content="<?php echo LegacyContainer::getParameter('legacy_env_GOOGLE_SITE_VERIFICATION'); ?>" />
        <?php
    }
?>
    <?php

    // _________________________________________________ HEADER AU CHOIX (inclut le doctype)
    if ($p_pageadmin) {
        require __DIR__ . '/includes/generic/header-admin.php';
    } else {
        require __DIR__ . '/includes/generic/header.php';
    }
// _________________________________________________ Ajout des CSS par page
if (is_array($p_addCss)) {
    foreach ($p_addCss as $handle) {
        if ($handle) {
            echo '<link rel="stylesheet" href="' . $handle . '" type="text/css"  media="screen" />' . "\n";
        }
    }
}
// _________________________________________________ Ajout des JS par page
if (is_array($p_addJs)) {
    foreach ($p_addJs as $handle) {
        if ($handle) {
            echo '<script type="text/javascript" charset="utf-8" src="' . $handle . '"></script>' . "\n";
        }
    }
}

?>
    <?php
        if (LegacyContainer::getParameter('legacy_env_DISPLAY_BANNER')) {
            ?>
        <style>
            #container, .fancybox-opened {
                margin-top: 50px !important;
            }
            #test-banner {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 30px;
                background-color: #f44336;
                color: #ffffff;
                text-align: center;
                z-index: 9999;
                padding: 10px 0;
                font-size: 16px;
                font-weight: bold;
            }
        </style>
    <?php
        }
?>
</head>
<body <?php if ('feuille-de-sortie' == $p1) { ?>id="feuille-de-sortie"<?php } ?>>
    <?php
    if (LegacyContainer::getParameter('legacy_env_DISPLAY_BANNER')) {
        ?>
        <div id="test-banner">
            <p>Attention, vous vous trouvez sur un site de test. Veuillez <a href="https://www.clubalpinlyon.fr">cliquer ici pour accéder au site de production</a>.</p>
        </div>
    <?php
    }
?>
    <div id="container">
        <div id="siteHeight">
            <?php
        // _________________________________________________ MENU ADMINISTRATEUR & gestionnaire de contenu
        if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER) && $p_pageadmin) {
            require __DIR__ . '/admin/menuAdmin.php';
        }

// _________________________________________________ CONTENU IMPRESSION FEUILLE SORTIE
if ('feuille-de-sortie' == $p1) {
    echo '<div id="pageAdmin" class="">';
    if (file_exists(__DIR__ . '/pages/' . $p1 . '.php')) {
        require __DIR__ . '/pages/' . $p1 . '.php';
    } else {
        require __DIR__ . '/pages/404.php';
    }
    echo '</div>';
}
// _________________________________________________ CONTENU COMMUN AUX PAGES PUBLIQUES
elseif (!$p_pageadmin || !isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    // include page
    require __DIR__ . '/includes/generic/top.php';
    require __DIR__ . '/includes/bigfond.php';
    if (file_exists(__DIR__ . '/pages/' . $p1 . '.php')) {
        require __DIR__ . '/pages/' . $p1 . '.php';
    } else {
        echo '<p class="erreur">Erreur d\'inclusion. Merci de contacter le webmaster.</p>';
    }
    require __DIR__ . '/includes/generic/footer.php';
}
// _________________________________________________ CONTENU PAGES ADMIN
else {
    echo '<div id="pageAdmin" class="">';
    if (file_exists(__DIR__ . '/pages/' . $p1 . '.php') && '404' != $p1) {
        require __DIR__ . '/pages/' . $p1 . '.php';
    } else {
        require __DIR__ . '/pages/404.php';
    }
    echo '</div>';
}
?>

            <!-- Waiters -->
            <div id="loading1" class="mybox-down"></div>
            <div id="loading2" class="mybox-up">
                <p><?php echo cont('operation-en-cours'); ?><br /><br /><img src="/img/base/loading.gif" alt="" title="" /></p>
            </div>

            <!-- affichage des manques de contenus en admin -->
            <?php
if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER) && count($contLog) && !$p_pageadmin) {
    echo '<div id="adminmissing">
                    <img src="/img/base/x.png" alt="" title="Fermer" style="float:right; cursor:pointer; padding:5px;" onclick="$(this).parent().fadeOut();" />
                    <div style="float:left; padding:12px 10px 3px 35px">Admin : champs non remplis dans cette page</div>';

    // si on est dans la langue par défaut, redirection vers la page des contenus :
    for ($i = 0; $i < count($contLog); ++$i) {
        $tmp = $contLog[$i];
        echo '<form style="display:inline" method="post" action="admin-contenus/fr.html">
                            <input type="hidden" name="operation" value="forceAddContent" />
                            <input type="text" readonly="readonly" name="code_content_inline" value="' . $tmp . '" onclick="$(this).parent().submit();" />
                        </form>';
    }

    echo '</div>';
}
?>


            <!-- lbxMsg : popup d'information -->
            <?php require __DIR__ . '/includes/generic/lbxMsg.php'; ?>

            <?php if (LegacyContainer::getParameter('legacy_env_ANALYTICS_ACCOUNT')) { ?>
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo LegacyContainer::getParameter('legacy_env_ANALYTICS_ACCOUNT'); ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', '<?php echo LegacyContainer::getParameter('legacy_env_ANALYTICS_ACCOUNT'); ?>');
            </script>

            <?php } ?>
        </div> <!--! end of #siteHeight -->
    </div>
    <?php echo LegacyContainer::get(name: 'legacy_twig')->render('components/modal.html.twig'); ?>
</body>
</html>
