<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<?php
        // article non trouvée, pas de message d'erreur, équivalent à un 404
        if (!$article && !$errPage) {
            echo '<br /><br /><br /><p class="erreur" style="margin:50px 20px 20px 20px">Hmmm... C\'est ennuyeux : nous n\'arrivons pas à trouver l\'article correspondant à cette URL.</p>';
        }
        // article non trouvée, avec message d'erreur, tentative d'accès mesquine ou sortié dévalidée
        if (!$article && $errPage) {
            echo '<br /><br /><br /><div class="erreur" style="margin:50px 20px 20px 20px">Erreur : Vous n\'avez pas accès à cette page. L\'article a peut-être été retiré par un responsable du site.</div>';
        }

        // article trouvée, pas d'erreur, affichage normal :
        if ($article && !$errPage) {
            // FICHE DE LA article
            require __DIR__.'/../includes/article-fiche.php';
        }
		?>
		<br style="clear:both" />
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__.'/../includes/right-type-agenda.php';
		?>


	<br style="clear:both" />
</div>