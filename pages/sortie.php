<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">
	
	<!-- partie gauche -->
	<div id="left1">
	
		<?php
		// sortie non trouvée, pas de message d'erreur, équivalent à un 404
		if(!$evt && !$errPage) echo '<br /><br /><br /><p class="erreur" style="margin:50px 20px 20px 20px">Hmmm... C\'est ennuyeux : nous n\'arrivons pas à trouver la sortie correspondant à cette URL.</p>';
		// sortie non trouvée, avec message d'erreur, tentative d'accès mesquine ou sortié dévalidée
		if(!$evt && $errPage) echo '<br /><br /><br /><div class="erreur" style="margin:50px 20px 20px 20px">Erreur : Vous n\'avez pas accès à cette page. La sortie a peut-être été retirée par un responsable du site.</div>';
		
		// sortie trouvée, pas d'erreur, affichage normal :
		if($evt && !$errPage){
			
			// FICHE DE LA SORTIE
			include INCLUDES.'evt-fiche.php';
			
		}
		?>
		<br style="clear:both" />
	</div>
	
	<!-- partie droite -->
	<?php
	include INCLUDES.'right-type-agenda.php';
	?>
	
	
	<br style="clear:both" />
</div>