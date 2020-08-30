<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define ('DS', DIRECTORY_SEPARATOR);
define ('ROOT', dirname(dirname(dirname(__FILE__))).DS);				// Racine
include (ROOT.'app'.DS.'includes.php');

//_________________________________________________ GESTION ET SECURISATIONS DES SESSIONS
include APP.'sessions.php';
//_________________________________________________ FONCTIONS MAISON
include APP.'fonctions.php';
//_________________________________________________ VARIABLES "GLOBALES" DU SITE
include APP.'params.php';
//_________________________________________________ MYSQLi
include SCRIPTS.'connect_mysqli.php';

?><?xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE html>
<html xmlns="https://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr" dir="ltr">
<head>
<title>CHRON</title>
</head>
<body onload="reloadTimeout()" style="font-family:Lucida Console; font-size:12px">

<?php
if(!admin()) echo 'Mode admin requis';
else{

	//_________________________________________________
	// cette page a pour objet d'effectuer les tâches automatisées du site

	// TRIGGER CAPITAL ! ACTIVE ET DESACTIVE L'EFFICACITE DU CHRON - ENVOI DE MAIL - INSERTION BDD
	$chron_sendmails=true;
	
	$req="SELECT * FROM caf_chron_launch ORDER BY tsp_chron_launch DESC LIMIT 1000";
	$handleSql=$mysqli->query($req);
	while($handle=$handleSql->fetch_array(MYSQLI_ASSOC)){
		echo '<h2>Appel : '.date('d/m/Y H:i:s', $handle['tsp_chron_launch']).'</h2>';
		
		$req="SELECT * FROM caf_chron_operation WHERE parent_chron_operation = ".intval($handle['id_chron_launch'])." ORDER BY tsp_chron_operation DESC LIMIT 1000";
		echo '<ul>';
		$handleSql2=$mysqli->query($req);
		while($handle2=$handleSql2->fetch_array(MYSQLI_ASSOC)){
			echo '<li>Opération : '.date('d/m/Y H:i:s', $handle2['tsp_chron_operation']).' : '.str_replace(';', ' <span style="color:orange">-</span> ', $handle2['code_chron_operation']).'</li>';
		}
		echo '</ul>';
	}

	$mysqli->close();

}

?>
</body>
</html>
