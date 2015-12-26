<?php

/* A PRIORI, FICHIER INUTILISE *//* ToDo : delete */

$errTab=array();
if(user()){
	
	$id_user=intval($_SESSION['user']['id_user']);
	$id_img=intval($_POST['id_img']); // facultatif
	$fichier_img=formater($_POST['fichier_img'], 4); // sécurité, empècher les ../
	
	if(!$fichier_img) $errTab[]="Erreur fichier_img vide";
	if(!$id_user) $errTab[]="Erreur id_user vide";
	
	// définition du dossier
	if(!sizeof($errTab)){
		// si c'est une image de base de donnée
		if($id_img){
			// récupération de l'id de la galerie
			include SCRIPTS.'connect_mysqli.php';
			$req="SELECT galerie_img FROM `".$pbd."img` WHERE `".$pbd."img`.`id_img` = $id_img AND user_img=".$id_user." LIMIT 1;";
			$handleSql = $mysqli->query($req);
			while($handle=$handleSql->fetch_array(MYSQLI_ASSOC)){
				$dossier='../ftp/galeries/'.$handle['galerie_img'];
			}
		}
		// sinon c'est une image de création de sortie
		else{
			$dossier='../ftp/user/'.$id_user.'/transit-nouvellesortie';
		}
		
		// checks
		if(!$dossier) $errTab="Dossier indéfini";
		if(!file_exists($dossier)) $errTab="Dossier inexistant ($dossier)";
	}
	
	// suppression dans la BD
	if(!sizeof($errTab) && $id_img){
		include SCRIPTS.'connect_mysqli.php';
		$req="DELETE FROM `".$pbd."img` WHERE `".$pbd."img`.`id_img` = $id_img AND user_img=".$id_user." LIMIT 1;";
		if(!$mysqli->query($req))	$errTab[]="Erreur SQL lors de la suppression (img=$id_img et user=$id_user)";
		$mysqli->close();
	}
	
	// suppression du fichier
	if(!sizeof($errTab)){
		// originale
		if(is_file($dossier.'/'.$fichier_img))			unlink($dossier.'/'.$fichier_img);
		// min
		if(is_file($dossier.'/'.'min-'.$fichier_img))	unlink($dossier.'/'.'min-'.$fichier_img);
		// pic
		if(is_file($dossier.'/'.'pic-'.$fichier_img))	unlink($dossier.'/'.'pic-'.$fichier_img);
	}
}
else $errTab[]='not logged';



/* */
// envoi du résultat :
if(sizeof($errTab)){
	$result['success']=0;
	$result['error']=implode(', ', $errTab);
}
else $result['success']=1;

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

/* *

$log.="\n  errTab :";
foreach($errTab as $key=>$value)
	$log.="\n $key = $value";
	
	
$fp = fopen('dev.txt', 'w');fwrite($fp, $log);fclose($fp);
/* */
