<?php

	include SCRIPTS.'connect_mysqli.php';

	$nom_content_inline_group=$mysqli->real_escape_string(trim(stripslashes($_POST['nom_content_inline_group'])));

	// checks
	if(!strlen($nom_content_inline_group))					$errTab[]="Entrez un nom";
	$req="SELECT COUNT(*) FROM ".$pbd."content_inline_group WHERE nom_content_inline_group LIKE '$nom_content_inline_group' ";
	$handleCount=$mysqli->query($req);
	if(getArrayFirstValue($handleCount->fetch_array(MYSQLI_NUM))) $errTab[]="Erreur : ce groupe existe déjà dans la liste";

	/* */
	if(!sizeof($errTab)){
		$nom_content_inline_group=$mysqli->real_escape_string($nom_content_inline_group);

		$req="INSERT INTO `".$pbd."content_inline_group` (`id_content_inline_group` ,`ordre_content_inline_group` ,`nom_content_inline_group`)
														VALUES (NULL , '', '$nom_content_inline_group');";
		if(!$mysqli->query($req)) $erreur="Erreur BDD<br />".$req;
		$id_content_inline_group=$mysqli->insert_id;
		$req="UPDATE `".$pbd."content_inline_group` SET `ordre_content_inline_group` = '$id_content_inline_group' WHERE `".$pbd."content_inline_group`.`id_content_inline_group` =$id_content_inline_group LIMIT 1 ;";
		if(!$mysqli->query($req)) $erreur="Erreur BDD<br />".$req;

	}
	/* */
	$mysqli->close();
?>