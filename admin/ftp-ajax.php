<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define ('DS', DIRECTORY_SEPARATOR );
define ('ROOT', dirname(dirname(__FILE__)).DS);				// Racine
include (ROOT.'app'.DS.'includes.php');

$racine = '../ftp/';

$errTab=array();
$dirTab=array();
$fileTab=array();
$result;

if(!admin()){
	$errTab[]='Votre session administrateur a expiré';
}

// vars et checks
if(!sizeof($errTab)){
	$dossier=$_GET['dossier'];
	// checks : 
	if(substr($dossier, 0, strlen($racine))!=$racine or mb_substr_count($dossier, '../')>1){
		$errTab[]="Le dossier demandé ($dossier) n'a pas le bon format.";
	}
	if(!file_exists($dossier)) $errTab[]="Ce dossier est introuvable";
}

// listage
if(!sizeof($errTab)){
	$one=false; // booleen : un dossier trouve au moins
	$opendir=opendir($dossier);
	while($file=readdir($opendir)){
		// c'est un dossier, non masqué
		if(is_dir($dossier.$file) && !in_array($file, $p_ftp_masquer)){
			$one=true;
			$dirTab[]=$file;
		}
		// c'est un fichier, non masqué
		if(!is_dir($dossier.$file) && !in_array($file, $p_ftp_masquer)){
			$one=true;
			$tmp=array();
			$tmp['name']=$file;
			$tmp['filesize']=filesize($dossier.$file);
			$tmp['filemtime']=filemtime($dossier.$file);
			$tmp['filetype']=filetype($dossier.$file);
			$tmp['ext']=substr(strrchr($file, '.'), 1);
			$imgDim=getimagesize($dossier.$file);
			$tmp['imgw']=intval($imgDim[0]);
			$tmp['imgh']=intval($imgDim[1]);
			// $tmp['stat']=stat($dossier.$file);
			$fileTab[]=$tmp;
		}
	}
}


if(sizeof($errTab)) $result['error']=$errTab;
else{
	$result['success']=1;
	$result['dirTab']=$dirTab;
	$result['fileTab']=$fileTab;
}
// affichage resultat
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
