<?php


	$id_article=intval($_POST['id_article']);

	include SCRIPTS.'connect_mysqli.php';;
	$req="DELETE FROM caf_article WHERE id_article=$id_article AND status_article!=1 ";
	if (allowed('article_delete_notmine')){
		$req .= " ";
	} else {
		$req .= " AND user_article=".intval($_SESSION['user']['id_user']);
	}
	if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
	elseif($mysqli->affected_rows < 1) $errTab[]="Aucun enregistrement affecté";

	if(!sizeof($errTab)){
		// suppression du dossier
		if($id_article && is_dir('ftp/articles/'.$id_article))
			clearDir('ftp/articles/'.$id_article);
	}

	$mysqli->close;

	header('Location: /gestion-des-articles.html');
	exit();

?>