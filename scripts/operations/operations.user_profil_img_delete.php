<?php

	$id_user=intval($_SESSION['user']['id_user']);

	if(!$id_user) $errTab[]="Erreur id";

	if(!sizeof($errTab)){
		if(is_file('ftp/user/'.$id_user.'/min-profil.jpg'))	unlink('ftp/user/'.$id_user.'/min-profil.jpg');
		if(is_file('ftp/user/'.$id_user.'/min-profil.png'))	unlink('ftp/user/'.$id_user.'/min-profil.png');
		if(is_file('ftp/user/'.$id_user.'/profil.jpg'))	unlink('ftp/user/'.$id_user.'/profil.jpg');
		if(is_file('ftp/user/'.$id_user.'/profil.png'))	unlink('ftp/user/'.$id_user.'/profil.png');
	}

?>