<?php

if(!admin()){
	echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
	exit;
}
$uploaddir = 'ftp/partenaires/';


$part_id=intval($_POST['part_id']);
$partenaireTab['part_image'] = trim($_POST['part_image']);

include 'scripts/connect_mysqli.php';
$req="DELETE FROM `".$pbd."partenaires` WHERE part_id='".$mysqli->real_escape_string($part_id)."'";

if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
elseif($mysqli->affected_rows < 1) $errTab[]="Aucun enregistrement affecté";
else{
	if(is_file($uploaddir.$partenaireTab['part_image'])){
		//delete old file
		unlink($uploaddir.$partenaireTab['part_image']);
	}
}

$mysqli->close;

exit();

?>