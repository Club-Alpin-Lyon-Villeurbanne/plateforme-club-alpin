<?php


	$id_article=intval($_POST['id_article']);

	include SCRIPTS.'connect_mysqli.php';

	$req="UPDATE caf_article SET tsp_validate_article=".$p_time." WHERE caf_article.id_article=$id_article"; // premiere validation

	if (!allowed('article_validate_all')){
		$req .= " AND user_article=".intval($_SESSION['user']['id_user']);
	}

	if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
	elseif($mysqli->affected_rows < 1) $errTab[]="Aucun enregistrement affecté";

	$mysqli->close;



?>