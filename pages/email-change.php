<?php
	echo '<div class="contenutype1" style="margin:auto;"><h2 style="color:gray">Reinitialisation de l\'email...</h2>';
	if(sizeof($errTab)) echo '<div class="erreur"><b>ERREURS : </b>'.implode(', ', $errTab).'</div>';
	else{
		echo '<h1>Succès</h1><p>Vous pouvez vous connecter avec votre nouvelle adresse e-mail.</p>';
	}
	echo '<a class="nice2 green" href="'.$p_racine.'profil.html" title="">Continuer</a>';
	echo '</div>';