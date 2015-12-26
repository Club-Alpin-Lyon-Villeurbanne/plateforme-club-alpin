<?php
	$id_article			=intval($p2);
	$status_article		=0;
	$topubly_article	= ($_POST['topubly_article']=='on'?1:0);
	// $tsp_crea_article	=$p_time;
	// $tsp_article		=$p_time;
	// $user_article		=intval($_SESSION['user']['id_user']);
	$titre_article		=stripslashes($_POST['titre_article']);
	// $code_article		=substr(formater($titre_article, 3), 0, 30);
	$commission_article	=intval($_POST['commission_article']);
	$evt_article		=intval($_POST['evt_article']);
	$une_article		=($_POST['une_article']=='on'?1:0);
	$cont_article		=stripslashes($_POST['cont_article']);

	// CHECKS
	if($_POST['commission_article'] =='') $errTab[]="Merci de sélectionner le type d'article";
	// if(!$user_article) $errTab[]="ID User invalide";
	if(strlen($titre_article)<3) $errTab[]="Merci de rentrer un titre valide";
	if(strlen($titre_article)>200) $errTab[]="Merci de rentrer un titre inférieur à 200 caractères";
	if($commission_article == -1 && !$evt_article) $errTab[]="Si cet article est un compte rendu de sortie, veuillez sélectionner la sortie liée.";
	if(strlen($cont_article)<10) $errTab[]="Merci de rentrer un contenu valide";
	// image
	/*
	if(
		!file_exists('ftp/articles/'.$id_article.'/figure.jpg')
		or !file_exists('ftp/articles/'.$id_article.'/wide-figure.jpg')
		or !file_exists('ftp/articles/'.$id_article.'/min-figure.jpg')
		 $errTab[] = "Les images liées sont introuvables";
		 */


	// enregistrement en BD
	if(!sizeof($errTab)){

		include SCRIPTS.'connect_mysqli.php';;
		$titre_article 	= $mysqli->real_escape_string($titre_article);
		$code_article 	= $mysqli->real_escape_string($code_article);
		$cont_article 	= $mysqli->real_escape_string($cont_article);

		$req="UPDATE caf_article
		SET status_article = $status_article
		, topubly_article = $topubly_article
		, titre_article = '$titre_article'
		, commission_article = $commission_article
		, evt_article = $evt_article
		, une_article = $une_article
		, cont_article = '$cont_article'
		, tsp_article=".$p_time."
		WHERE id_article = $id_article
		"
		// on verifie si on est l'auteur que si on a pas le droit de modifier TOUS les articles
		.(allowed('article_edit_notmine')?'':" AND user_article = ".intval($_SESSION['user']['id_user']))
		;
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		elseif($mysqli->affected_rows < 1) $errTab[]="Aucun enregistrement affecté : ID introuvable, ou vous n'êtes pas le créateur de cette article, ou bien aucune modification n'a été apportée.";

		$mysqli->close;

	}

	// debug : reload page
	if(!sizeof($errTab)){
		header("Location: $p_racine"."article-edit/$id_article.html?lbxMsg=article_edit_success");
	}
?>