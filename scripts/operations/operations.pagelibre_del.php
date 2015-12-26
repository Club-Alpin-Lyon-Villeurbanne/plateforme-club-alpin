<?php

	include SCRIPTS.'connect_mysqli.php';
	$id_page=intval($_POST['id_page']);

	if(!$id_page) $errTab[]="ID manquant";
	if($_POST['confirm']!='SUPPRIMER') $errTab[]="Vous devez recopier le texte approprié pour confirmer la suppression.";

	if(!sizeof($errTab)){
		$req="DELETE FROM ".$pbd."page WHERE id_page=$id_page LIMIT 1";
		if(!$mysqli->query($req)) $erreur="Erreur BDD<br />".$req;

		$req="DELETE FROM ".$pbd."content_html WHERE code_content_html LIKE 'pagelibre-$id_page'";
		if(!$mysqli->query($req)) $erreur="Erreur BDD2<br />".$req;
	}

	$mysqli->close();

	if(!sizeof($errTab)) mylog("pagelibre-delete", "Suppression de la page libre id=$id_page");


?>