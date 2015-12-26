<?php
if(!allowed('user_reset')){
	echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
}
else{

	$id_user=intval($_GET['id_user']);
	if(!$id_user){
		echo 'Erreur : id invalide';
		exit();
	}
	?>
	
	<div style="text-align:left;">
		<h1>Remettre à zéro le compte de l'utilisateur : <?php echo html_utf8(stripslashes($_GET['nom']));?></h1><br />
		
		<p>
			Voulez-vous vraiment remettre à zéro ce compte utilisateur ? 
			Cette personne devra à nouveau choisir un e-mail et un mot de passe, comme à la création de son compte, mais
			son historique, ses sorties et articles seront conservés. Cet utilisateur apparaîtra dans la liste des adhérents non validés jusqu'à
			ce qu'il active à nouveau son compte.
		</p>
		
		<form action="<?php echo $versCettePage;?>" method="post">
			<input type="hidden" name="operation" value="user_reset" />
			<input type="hidden" name="id_user" value="<?php echo $id_user;?>" />
			
			<?php
			// TABLEAU
			if($_POST['operation'] == 'user_reset' && sizeof($errTab))	echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
			if($_POST['operation'] == 'user_reset' && !sizeof($errTab))	echo '<p class="info">Utilisateur réinitialisé (Vous devrez <a href="adherents.html" title="" target="_top">actualiser la page</a> pour voir le changement)</p>';
			else{
				?>
				<input type="submit" class="nice2 orange" value="Réinitialiser cet adhérent" />
				<a href="javascript:top.$.fancybox.close()" title="" class="nice2">Annuler</a>
				<?php
				
			}
			?>
		</form>
	</div>
	
	
	<?php
}
?>