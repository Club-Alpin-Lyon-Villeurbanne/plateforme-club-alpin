<h2>Validation de votre compte...</h2>

<?php
// MESSAGES
if(sizeof($errTab))	echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
else{
	
	?>
	<h3>Votre compte est validé</h3>
	<p>
		Vous allez être redirigé vers <a href="profil.html" title="Prendre un raccourci">votre espace personnel</a> dans 
		<span id="decompte"></span> secondes.
	</p>
	<script type="text/javascript">
		var compte=10;
		function decompte(){
			$('#decompte').html(compte);
			compte--;
			if(compte > -1)	setTimeout('decompte()', 1000);
			else window.location.href='profil.html';
			
		}
		
		$().ready(function(){
			decompte();
		});
	</script>
	<?php
}