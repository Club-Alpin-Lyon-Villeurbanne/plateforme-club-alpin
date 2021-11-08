<?php
include __DIR__.'/../../includes/browser-alert.php';
?>

<header id="mainHeader">
	<div class="sitewidth" style="min-width:980px;">

		<?php
        // DEV TOOL : visualiser la liste des actions autorisées
        /*
        echo '<pre>';
        print_r($userAllowedTo);
        echo '</pre>';
        */
        ?>

		<!-- LOGO COMMUN TTES PAGES -->
		<a id="logo" href="<?php echo $p_racine; ?>" title="<?php echo cont('logo-title'); ?>"><img src="/img/logo.png" alt="<?php echo cont('logo-title'); ?>" title="<?php echo cont('logo-title'); ?>" /></a>


		<!-- COMMISSION -->
		<a id="toolbar-commission" href="javascript:void(0)" title="<?php echo $current_commission ? $comTab[$current_commission]['title_commission'] : ''; ?>" class="toptrigger">
			<div style="position:absolute;"><span id="shadowcache-commission" class="shadowcache"></span></div>
			<?php
            // comm visible ou message par defaut :
            if (!$current_commission) {
                echo '<span class="picto"><img src="/img/comm-please.png" alt="" title="" class="light" /><img src="/img/comm-please-up.png" alt="" title="" class="dark" /></span> Commissions - Activités<br /><b>choisissez...</b>';
            } else {
                echo '<span class="picto"><img src="'.comPicto($comTab[$current_commission]['id_commission']).'" alt="" title="" class="light" /><img src="'.comPicto($comTab[$current_commission]['id_commission'], 'dark').'" alt="" title="" class="dark" /></span> Commission - Activité :<br /><b>'.html_utf8($comTab[$current_commission]['title_commission']).'</b>';
            }
            ?>
		</a>
		<!-- PARTIE CACHEE -->
		<nav id="toolbar-commission-hidden" <?php /* * if(admin()) echo 'style="top:124px"'; /* */ ?>>
			<div class="sitewidth">
				<a href="<?php echo $p_racine; ?>" title=""><span class="picto" style="background-image:url(<?php echo comPicto(0, 'light'); ?>)"><img src="<?php echo comPicto(0, 'dark'); ?>" alt="" title="" /></span> Toutes les commissions</a>
				<?php
                foreach ($comTab as $code => $com) {
                    // Par défaut, choisir une commission redirige vers l'acceuil
                    $tmp = 'accueil';
                    // cas pour lesquells un choix de commission redirige vers la même page
                    switch ($p1) {
                        case 'agenda':
                        // case 'recherche':
                        case 'article-new':
                            $tmp = $p1; break;
                    }
                    // lien
                    echo '<a href=" '.$tmp.'/'.$code.'.html" title=""><span class="picto" style="background-image:url('.comPicto($com['id_commission'], 'light').')"><img src="'.comPicto($com['id_commission'], 'dark').'" alt="" title="" /></span> '.$com['title_commission'].'</a> ';
                }
                ?>
			</div>
		</nav>


		<!-- NAVIGATION -->
		<a id="toolbar-navigation" href="javascript:void(0)" title="" class="toptrigger">
			<span class="picto"><img src="/img/boussole.png" alt="" title="" class="light" /><img src="/img/boussole-up.png" alt="" title="" class="dark" /></span>
			La carte du site :<br /><b>Navigation</b>
			<span id="shadowcache-navigation" class="shadowcache"></span>
		</a>
		<!-- PARTIE CACHEE -->
		<nav id="toolbar-navigation-hidden" <?php /* * if(admin()) echo 'style="top:124px"'; /* */ ?>>
			<div class="sitewidth">
				<?php
                inclure('nav-menu-1', 'nav-menu');
                inclure('nav-menu-2', 'nav-menu');
                inclure('nav-menu-3', 'nav-menu');
                inclure('nav-menu-4', 'nav-menu');
                ?>
			</div>
		</nav>


		<?php
        include __DIR__.'/../../includes/generic/user_tools.php';
        ?>

	</div>
</header>
<div id="top-openers">
	<div class="sitewidth">
		<span class="opener" style="left:480px;"><img src="/img/opener-commission.png" alt="" title="" /></span>
		<span class="opener" style="left:630px;"><img src="/img/opener-navigation.png" alt="" title="" /></span>
		<span class="opener" style="left:820px;"><img src="/img/opener-user.png" alt="" title="" /></span>
	</div>
</div>

<!-- balise a : permet de focaliser dessus = masquer les menu dans une navigation au clavier / voir js/onready-site.js-->
<a href="javascript:void(0)" id="top-hider"></a>














