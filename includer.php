<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define ('DS', DIRECTORY_SEPARATOR );
define ('ROOT', dirname(__FILE__).DS);				// Racine
include (ROOT.'app'.DS.'includes.php');

$p=$_GET['p'];
$pageAdmin=$_GET['admin'];

// verification d'inclusion
if(substr($p, 0, 6)!='pages/' && substr($p, 0, 9)!='includes/') 	echo 'err 1';
elseif(strpos($p, '../')) 		echo 'err 2';
elseif(strpos($p, 'https://')) 		echo 'err 3';
else{
	// lien vers cette page (pour formulaires, ou ancres)
	$versCettePage = 'includer.php?null=0';
	foreach($_GET as $key=>$val) $versCettePage.='&'.$key.'='.$val;

	//_________________________________________________ HEADER AU CHOIX (inclut le doctype)
	if($pageAdmin)			include 'pages/header-admin.php';
	else					include 'pages/header.php';

	echo '<div id="includer-stuff">';
	if(file_exists($p)) include $p;
	else echo 'Fichier introuvable : '.$p;
	echo '</div>
	<!-- Waiters -->
	<div id="loading1" class="mybox-down"></div>
	<div id="loading2" class="mybox-up">
		<p>Opération en cours<br /><br /><img src="img/base/loading.gif" alt="" title="" /></p>
	</div>
	';
}
?>