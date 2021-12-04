<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<?php
        // La vérification de la page se fait dans le fichier APP/pages.php
        // Tout simplement, il y a erreur si la var $currentPage2 est unset ou vide
        if (!$currentPage2) {
            echo '<br /><br /><p class="erreur">Erreur 404 : page introuvable</p>';
        } else {
            // cette page est-elle visible aux visiteurs ?
            if (!$currentPage2['vis_page']) {
                echo '<p class="info" style="position:relative; top:35px;">Cette page n\'est pas accessible aux visiteurs du site. Pour la rendre visible, prenez le temps de modifier les contenus ci-dessous, puis rendez-vous dans <a href="admin-pages-libres.html" title="">l\'espace d\'administration des pages</a>.</p><br />';
            }

            echo '<h1 class="page-h1">'.$meta_title.'</h1>';

            inclure('main-pagelibre-'.$currentPage2['id_page'], 'main-type');
        }
        ?>
	</div>

	<!-- partie droite -->
	<?php
    include __DIR__.'/../includes/right-type-agenda.php';
    ?>

	<br style="clear:both" />
</div>