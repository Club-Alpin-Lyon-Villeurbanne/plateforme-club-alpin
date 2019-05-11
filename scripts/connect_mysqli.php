<?php

ini_set('display_errors','off');

	//_________________________________________________ BASE DE DONNEES
	include APP.'db_config.php';

	$serveur_bd 	= $p_serveur_bd;
	$login_bd 		= $p_login_bd;
	$pass_bd 		= $p_pass_bd;
	$nom_bd 		= $p_nom_bd;
	$port_bd		= $p_port_bd;

	// CRI - 17/01/2016 : ajout du port MySQL pour compatibilité avec serveurs MySQL utilisant un port différent 
	// du port par défaut (3306)
	// mysqli -> Modification
	if ($port_bd !='' && is_numeric($port_bd)){
		$mysqli = new mysqli($serveur_bd, $login_bd, $pass_bd, $nom_bd, $port_bd);
	} else {
		$mysqli = new mysqli($serveur_bd, $login_bd, $pass_bd, $nom_bd);
	}

	// CRI- 23/01/2016 : Message d'erreur si impossible de se connecter à la base
	// Evite les erreurs PHP
	if ($mysqli->connect_errno){
		die("Impossibe de se connecter à la base de données. Merci d'avertir l'administrateur.");
	}
	$mysqli->set_charset("UTF8");
