<?php

	//_________________________________________________ BASE DE DONNEES
	include APP.'db_config.php';

	$serveur_bd 	= $p_serveur_bd;
	$login_bd 		= $p_login_bd;
	$pass_bd 		= $p_pass_bd;
	$nom_bd 		= $p_nom_bd;

	// mysqli
	$mysqli = new mysqli($serveur_bd, $login_bd, $pass_bd, $nom_bd);
	$mysqli->set_charset("UTF8");
