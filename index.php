<?php
ini_set('display_errors','off');

    //_________________________________________________ DEFINITION DES DOSSIERS
    define ('DS', DIRECTORY_SEPARATOR );
    define ('ROOT', dirname(__FILE__).DS);              // Racine
    include (ROOT.'app'.DS.'includes.php');


// ________________________________________________ TRAITEMENT AJAX
    if (isset($_GET['ajx'])) {
        $ajaxFile = APP.'ajax'.DS.$_GET['ajx'].'.php';
        include $ajaxFile;
        exit;
    }

    // Géré par .htaccess
    if (isset($_GET['cstImg'])) {
        include APP.'custom_image.php';
        exit;
    }

    // lien vers cette page (pour formulaires, ou ancres)
    $versCettePage = ($p_multilangue?$lang.'/':'').$p1.($p2?'/'.$p2:'').($p3?'/'.$p3:'').($p4?'/'.$p4:'').'.html';			// multilangue / une langue

    header('Content-Type: text/html; charset=utf-8');
    
?><!doctype html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="utf-8">
    <title>
    <?php echo html_utf8($meta_title);?>
    <?php if ($p1 == 'feuille-de-sortie') { ?> 
        <?php if ($evt) { ?>
        - Feuille de sortie - <?php echo html_utf8($evt['titre_evt']); ?>-<?php echo date('d.m.Y', $evt['tsp_evt']); ?>
        <?php } else { ?>
        - Feuille de destination - <?php echo html_utf8($destination['nom']); ?> - le <?php echo display_date($destination['date']); ?> à <?php echo display_time($destination['date']); ?>
        <?php } ?>
    <?php } ?>
    </title>
    <base href="<?php echo $p_racine; ?>" />
    <meta name="description" content="<?php echo html_utf8($meta_description);?>">
    <meta name="author" content="www.herewecom.fr">
    <meta name="viewport" content="width=1200">
	<?php if (isset($p_google_site_verification) && !empty($p_google_site_verification)) { ?><meta name="google-site-verification" content="<?php echo $p_google_site_verification; ?>" /><?php } ?>
    <?php
    
        //_________________________________________________ HEADER AU CHOIX (inclut le doctype)
        if($p_pageadmin)	include INCLUDES.'generic'.DS.'header-admin.php';
        else				include INCLUDES.'generic'.DS.'header.php';
        //_________________________________________________ Ajout des CSS par page
        if(is_array($p_addCss))	foreach($p_addCss as $handle) if($handle) echo '<link rel="stylesheet" href="'.$handle.'" type="text/css"  media="screen" />'."\n";
        //_________________________________________________ Ajout des JS par page
        if(is_array($p_addJs))	foreach($p_addJs as $handle) if($handle) echo '<script type="text/javascript" charset="utf-8" src="'.$handle.'"></script>'."\n";

    ?>
    <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>
<body>
    <div id="container">
        <div id="siteHeight">
            <?php
                //_________________________________________________ MENU ADMINISTRATEUR
                if(admin())	include ADMIN.'menuAdmin.php';

                //_________________________________________________ CONTENU IMPRESSION FEUILLE SORTIE
                if ($p1 == 'feuille-de-sortie') {
                    echo '<div id="pageAdmin" class="'.($currentPage['superadmin_page']?'superadmin':'').'">';
                    if(file_exists(PAGES.'/'.$p1.'.php')) include PAGES.'/'.$p1.'.php';
                    else include '404.htm';
                    echo '</div>';
                } 
                //_________________________________________________ CONTENU COMMUN AUX PAGES PUBLIQUES
                elseif(!$p_pageadmin or !admin()){
                    // include page
                    include INCLUDES.'generic'.DS.'top.php';
                    include INCLUDES.'bigfond.php';
                    if(file_exists(PAGES.'/'.$p1.'.php')) include PAGES.'/'.$p1.'.php';
                    else echo '<p class="erreur">Erreur d\'inclusion. Merci de contacter le webmaster.</p>';
                    include INCLUDES.'generic'.DS.'footer.php';
                }
                //_________________________________________________ CONTENU PAGES ADMIN
                else{
                    echo '<div id="pageAdmin" class="'.($currentPage['superadmin_page']?'superadmin':'').'">';
                    if(file_exists(PAGES.'/'.$p1.'.php') && $p1 != '404') include PAGES.'/'.$p1.'.php';
                    else include '404.htm';
                    echo '</div>';
                }
            ?>

            <!-- Waiters -->
            <div id="loading1" class="mybox-down"></div>
            <div id="loading2" class="mybox-up">
                <p><?php echo cont('operation-en-cours');?><br /><br /><img src="img/base/loading.gif" alt="" title="" /></p>
            </div>

            <!-- affichage des manques de contenus en admin -->
            <?php
            if(admin() and sizeof($contLog) and !$p_pageadmin){
                echo '<div id="adminmissing">
                    <img src="img/base/x.png" alt="" title="Fermer" style="float:right; cursor:pointer; padding:5px;" onclick="$(this).parent().fadeOut();" />
                    <div style="float:left; padding:12px 10px 3px 35px">Admin : champs non remplis dans cette page</div>';

                // si on est dans la langue par défaut, redirection vers la page des contenus :
                if($lang==$p_langs[0]){
                    for($i=0; $i<sizeof($contLog); $i++){
                        $tmp=$contLog[$i];
                        echo '<form style="display:inline" method="post" action="'.($p_multilangue?$lang.'/':'').'admin-contenus/'.$lang.'.html">
                                <input type="hidden" name="operation" value="forceAddContent" />
                                <input type="text" readonly="readonly" name="code_content_inline" value="'.$tmp.'" onclick="$(this).parent().submit();" />
                            </form>';
                    }
                }
                // si on est sur une page dans une autre langue, redirection vers la page traductions
                else{
                    echo '<a href="'.$lang.'/admin-traductions/'.$lang.'.html" title="">&gt; Voir la page de traduction</a>';
                }
                echo '</div>';
            }
            ?>


            <!-- lbxMsg : popup d'information -->
            <?php include (INCLUDES.'generic'.DS.'lbxMsg.php'); ?>
            

            <script type="text/javascript">

                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', '<?php echo $p_google_analytics_account; ?>']);
                _gaq.push(['_trackPageview']);

                (function() {
                    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                })();

            </script>
        </div> <!--! end of #siteHeight -->
    </div> <!--! end of #container -->
</body>
</html>
