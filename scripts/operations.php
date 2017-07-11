<?php
// quitter la session admin
if($_GET['quitadmin']) admin_stop();
// quitter la session user
if($_GET['user_logout']) { user_logout(); header("Location: accueil.html"); }

// if($_POST['operation'])	$errTab=array();
$errTab=array();
$operationsDir = SCRIPTS.'operations'.DS;

/** -------------------------- **/
/** OPERATIONS SPECIFIQUES CAF **/
/** -------------------------- **/

// SPECIAL : REINIT MDP : seconde étape (confirmation depuis le lien dans l'email
if($p1=='mot-de-passe-perdu' && $p2){
	include ($operationsDir.'operations.mot-de-passe-perdu.php');
}

// SPECIAL : REINIT EMAIL : seconde étape (confirmation depuis le lien dans l'email
if($p1=='email-change' && $p2){
	include ($operationsDir.'operations.email-change.php');
}

// SPECIAL : VALIDATION DE COMPTE USER
if($p1=='user-confirm'){
	include ($operationsDir.'operations.user-confirm.php');
}

// GOTO
if($p1=='goto' && $p2 && $p3){
	include ($operationsDir.'operations.goto.php');
}

// COMMISSIONS : ACTIVER / DESACTIVER
if($_POST['operation']=='commission_majvis' && user()){
	include ($operationsDir.'operations.commission_majvis.php');
}

// COMMISSIONS : REORGANISER
if($_POST['operation']=='commission_reorder' && user()){
	include ($operationsDir.'operations.commission_reorder.php');
}

// COMMISSIONS : CREATE
if($_POST['operation']=='commission_add' && user()){
	include ($operationsDir.'operations.commission_add.php');
}

// COMMISSIONS : EDIT
if($_POST['operation']=='commission_edit' && user()){
	include ($operationsDir.'operations.commission_edit.php');
	include ($operationsDir.'operations.groupe_edit.php');
}


// JOINS : USER / SORTIE : changement en bloc des statuts par l'organisateur
if($_POST['operation']=='user_join_update_status' && user()){
	include ($operationsDir.'operations.user_join_update_status.php');
}

// JOINS : USER / SORTIE : annulation
if($_POST['operation']=='user_join_del' && user()){
	include ($operationsDir.'operations.user_join_del.php');
}

// JOINS : USER / SORTIE : pré inscription + pré iscription affiliés
if($_POST['operation']=='user_join' && user()){
	include ($operationsDir.'operations.user_join.php');
}

// JOINS : USER / SORTIE : inscription manuelle de la part de l'organisateur de l'événemeent
if($_POST['operation']=='user_join_manuel' && user()){
	include ($operationsDir.'operations.user_join_manuel.php');
}

// JOINS : USER / SORTIE : inscription de nomade + création s'il n'existe pas deja
if($_POST['operation']=='user_join_nomade' && user()){
	include ($operationsDir.'operations.user_join_nomade.php');
}


// SORTIE : suppression
if($_POST['operation']=='evt_del' && user()){
	include ($operationsDir.'operations.evt_del.php');
}

// SORTIE : reactivation
if($_POST['operation']=='evt_uncancel' && user()){
	include ($operationsDir.'operations.evt_uncancel.php');
}

// SORTIE : annulation
if($_POST['operation']=='evt_cancel' && user()){
	include ($operationsDir.'operations.evt_cancel.php');
}

// SORTIE : publication OU refus
if($_POST['operation']=='evt_validate' && user()){
	include ($operationsDir.'operations.evt_validate.php');
}

// SORTIE : modification : remet le status à 0
if($_POST['operation']=='evt_update' && user()){
	include ($operationsDir.'operations.evt_update.php');
}

// SORTIE : création
if($_POST['operation']=='evt_create' && user()){
	include ($operationsDir.'operations.evt_create.php');
}

// SORTIE : validation légale ou refus
if($_POST['operation']=='evt_legal_update' && user()){
	include ($operationsDir.'operations.evt_legal_update.php');

}
// SORTIE : contacter les inscrits
if($_POST['operation']=='evt_user_contact' && user()){
	include ($operationsDir.'operations.evt_user_contact.php');
}



// DESTINATION : création
if($_POST['operation']=='dest_create' && user()){
	include ($operationsDir.'operations.dest_create.php');
}

// DESTINATION : update
if($_POST['operation']=='dest_update' && user()){
	include ($operationsDir.'operations.dest_update.php');
}

// DESTINATION : validation rapide / changement d'état
if(in_array($_POST['operation'], array('dest_validate', 'dest_lock', 'dest_annuler')) && user()){
    include ($operationsDir.'operations.dest_quick_update.php');
}

// DESTINATION : annulation
if($_POST['operation']=='dest_cancel' && user()){
    include ($operationsDir.'operations.dest_cancel.php');
}

// DESTINATION : enoiv emails cloture
if($_POST['operation']=='dest_mailer' && user()){
    include ($operationsDir.'operations.dest_mailer.php');
}

// BUS : update
if($_POST['operation']=='bus_update' && user()){
	include ($operationsDir.'operations.bus_update.php');
}

// ARTICLE : publication OU refus
if($_POST['operation']=='article_validate' && user()){
	include ($operationsDir.'operations.article_validate.php');
}

// ARTICLE : SUPPRIMER
if($_POST['operation']=='article_del' && user()){
	include ($operationsDir.'operations.article_del.php');
}

// ARTICLE : DÉPUBLIER
if($_POST['operation']=='article_depublier' && user()){
	include ($operationsDir.'operations.article_depublier.php');
}

// ARTICLE : MODIFIER
if($_POST['operation']=='article_update' && user()){
	include ($operationsDir.'operations.article_update.php');
}

// ARTICLE : CRÉER
if($_POST['operation']=='article_create' && user()){
	include ($operationsDir.'operations.article_create.php');
}

// ARTICLE : REMONTER EN TETE
if($_POST['operation']=='renew_date_article' && user()){
	include ($operationsDir.'operations.renew_date_article.php');
}

// ARTICLES : COMMENTER
if($_POST['operation']=='comment'){
	include ($operationsDir.'operations.comment.php');
}

// ARTICLES : SUPPRIMER UN COMMENTAIRE
if($_POST['operation']=='comment_hide'){
	include ($operationsDir.'operations.comment_hide.php');
}

// PARTENAIRE : EDIT or ADD
if($_POST['operation']=='partenaire_edit' || $_POST['operation']=='partenaire_add'){
	include ($operationsDir.'operations.partenaire_edit.php');
}

// PARTENAIRE : DELETE
if($_POST['operation']=='partenaire_delete' ){
	include ($operationsDir.'operations.partenaire_delete.php');
}

// USER : DELETE PROFIL IMG
if($_POST['operation']=='user_profil_img_delete' && user()){
	include ($operationsDir.'operations.user_profil_img_delete.php');
}

// USER : UPDATE PROFILE
if($_POST['operation']=='user_update'){
	include ($operationsDir.'operations.user_update.php');
}

// USER : UPDATE NIVEAU SPORTIF par commission
if($_POST['operation']=='user_update' || $_POST['operation']=='niveau_update'){
	include ($operationsDir.'operations.user_niveau_update.php');
}

// USER : TENTATIVE D'INSCRIPTION
if($_POST['operation']=='user_subscribe'){
	include ($operationsDir.'operations.user_subscribe.php');
}

// USER : LOGIN
if($_POST['operation']=='user_login'){
	include ($operationsDir.'operations.user_login.php');
}

// USER : ajout de l'attribut à l'user (type salarié, encadrant etc...)
if($_POST['operation']=='user_attr_add'){
	include ($operationsDir.'operations.user_attr_add.php');
}

// USER : supression d'attribut
if($_POST['operation']=='user_attr_del'){
	$errTab[]='tooddo';
	$id_user_attr=intval($_POST['id_user_attr']);
	if(!$id_user_attr) $errTab[]="No id";
	else{

		include SCRIPTS.'connect_mysqli.php';
		$req="DELETE FROM ".$pbd."user_attr WHERE id_user_attr = $id_user_attr LIMIT 1;";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		$mysqli->close();
	}
}
// USER : CREATE (manuel)
if($_POST['operation']=='user_create'){
	include ($operationsDir.'operations.user_create.php');
}

// USER : EDIT (manuel)
if($_POST['operation']=='user_edit'){
	include ($operationsDir.'operations.user_edit.php');
}

// USER : SUPPRIMER
if($_POST['operation']=='user_delete'){
	$id_user=intval($_POST['id_user']);
	if(!$id_user) $errTab[]="No id";
	elseif(!admin() || !allowed('user_delete')) $errTab[]="Vous n'avez pas les droits necessaires";
	else{

		include SCRIPTS.'connect_mysqli.php';
		// suppression participations aux sorties
		$req="DELETE FROM caf_evt_join WHERE caf_evt_join.user_evt_join=$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";

		// modification des articles de ce user (articles orphelins...)
		$req="UPDATE caf_article SET user_article=0 WHERE caf_article.user_article=$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";

		// suppression des droits
		$req="DELETE FROM caf_user_attr WHERE caf_user_attr.user_user_attr=$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";

		// suppression du user
		$req="DELETE FROM `caf_user` WHERE  `caf_user`.`id_user`=$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		
		mylog("user_delete", "Suppression definitive user $id_user", false);
		
		$mysqli->close();
	}
}

// USER : DESACTIVER
if($_POST['operation']=='user_desactiver'){
	$id_user=intval($_POST['id_user']);
	if(!$id_user) $errTab[]="No id";
	elseif(!allowed('user_desactivate_any')) $errTab[]="Vous n'avez pas les droits necessaires";
	else{

		include SCRIPTS.'connect_mysqli.php';
		$req="UPDATE `caf_user` SET  `valid_user` =  '2' WHERE  `caf_user`.`id_user` =$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		
		mylog("user_desactiver", "desactivation user $id_user", false);
		
		$mysqli->close();
	}
}
// USER : REACTIVER
if($_POST['operation']=='user_reactiver'){
	$id_user=intval($_POST['id_user']);
	if(!$id_user) $errTab[]="No id";
	elseif(!allowed('user_reactivate')) $errTab[]="Vous n'avez pas les droits necessaires";
	else{

		include SCRIPTS.'connect_mysqli.php';
		$req="UPDATE `caf_user` SET  `valid_user` =  '1' WHERE  `caf_user`.`id_user` =$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		
		mylog("user_reactiver", "reactivation user $id_user", false);
		
		$mysqli->close();
	}
}
// USER : RESET
if($_POST['operation']=='user_reset'){
	$id_user=intval($_POST['id_user']);
	if(!$id_user) $errTab[]="No id";
	elseif(!allowed('user_reset')) $errTab[]="Vous n'avez pas les droits necessaires";
	else{

		include SCRIPTS.'connect_mysqli.php';
		$req="UPDATE caf_user
				SET valid_user =  '0',
				email_user =  '',
				mdp_user =  ''
				WHERE caf_user.id_user =$id_user";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		
		mylog("user_reset", "reset user $id_user", false);
		
		$mysqli->close();
	}
}

// USER (OU PAS) : CONTACT
if($_POST['operation']=='user_contact'){
	include ($operationsDir.'operations.user_contact.php');
}


// MISE À JOUR DES FICHIERS ADHÉRENTS
if($_POST['operation']=='fichier_adherents_maj'){

	if(!allowed('user_updatefiles')) $errTab[]="Il semble que vous ne disposez pas des droits nécessaires";

	if(!sizeof($errTab)){
		$length = sizeof($_FILES['file']['name']);
		if($length<1) $errTab[]="Aucunes données reçues";
	}

	if(!sizeof($errTab)){
		$oneGood=false;
		for($i=0; $i<$length; $i++){
			if($_FILES['file']['name'][$i] == '7300.txt' || $_FILES['file']['name'][$i] == '7480.txt'){
				$oneGood=true;
				if(!move_uploaded_file($_FILES['file']['tmp_name'][$i] , './ftp/fichiers-proteges/'.$_FILES['file']['name'][$i]))
					$errTab[]="Erreur de déplacement du fichier ".$_FILES['file']['name'][$i];
					// $errTab[]="Erreur de déplacement du fichier ".$_FILES['file']['name'][$i]." vers ".'ftp/fichiers-proteges/'.$_FILES['file']['name'][$i];
			}
		}

		if(!$oneGood) $errTab[]="Aucun fichier reçu ne correspond, opération ignorée";
	}

}


// USER : RESET MDP
if($_POST['operation']=='user_mdp_reinit'){
	include ($operationsDir.'operations.user_mdp_reinit.php');
}

// ADMIN : ajout de l'attribut à l'user (type admin, rédacteur etc...)
if($_POST['operation']=='user_attr_add_admin' && admin()){
	include ($operationsDir.'operations.user_attr_add_admin.php');
}

// ADMIN : supression d'attribut
if($_POST['operation']=='user_attr_del_admin' && admin()){
	$id_user_attr=intval($_POST['id_user_attr']);
	if(!$id_user_attr) $errTab[]="No id";
	else{

		include SCRIPTS.'connect_mysqli.php';
		$req="DELETE FROM ".$pbd."user_attr WHERE id_user_attr = $id_user_attr LIMIT 1;";
		if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
		$mysqli->close();
	}

	// log admin
	if(!sizeof($errTab)) mylog($_POST['operation'], "Suppression d'un droit à un user (id=$id_user_attr)");
}

// ADMIN : écrasement et renouvellement de la matrice des droits
if($_POST['operation']=='usertype_attr_edit' && admin()){

	/* ◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊[ BACKUP EXISTANT A FAIRE - ou pas ]◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊ */
	include SCRIPTS.'connect_mysqli.php';

	// supression des valeurs existantes
	if(!$mysqli->query("TRUNCATE ".$pbd."usertype_attr")) $errTab[]="Erreur à la réinitialisation de la table";
	if(!sizeof($errTab)){
		foreach($_POST['usertype_attr'] as $pair){
			$tab=explode('-', $pair);
			$type_usertype_attr=intVal($tab[0]);
			$right_usertype_attr =intVal($tab[1]);
			if(!$mysqli->query("INSERT INTO ".$pbd."usertype_attr (id_usertype_attr, type_usertype_attr, right_usertype_attr, details_usertype_attr)
															VALUES (NULL , '$type_usertype_attr', '$right_usertype_attr', '$p_time');"))
				$errTab[]="Erreur de setting ($type_usertype_attr - $right_usertype_attr)";
		}
	}
	$mysqli->close();
}

// ADMIN: modification de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
if($_POST['operation']=='pagelibre_edit' && admin()){
	include ($operationsDir.'operations.pagelibre_edit.php');
}

// ADMIN: ajout de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
if($_POST['operation']=='pagelibre_add' && admin()){
	include ($operationsDir.'operations.pagelibre_add.php');
}

// ADMIN: suppression de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
if($_POST['operation']=='pagelibre_del' && admin()){
	include ($operationsDir.'operations.pagelibre_del.php');
}

// ADMIN : VOL DE SESSION (variables GET pour ce coup là)
if($_GET['operation']=='steal_session' && admin()){
	$email_user=$_GET['email_user'];
	mylog('steal_session', "infiltration d'un user ($email_user)");
	user_login($email_user);
}

/** -------------------------- **/
/** OPERATIONS BASE      **/
/** -------------------------- **/

// BASE: page add
if($_POST['operation']=='page_add' && superadmin()){
	include ($operationsDir.'operations.page_add.php');

}
// BASE: page del
if($_POST['operation']=='page_del' && superadmin()){
	include ($operationsDir.'operations.page_del.php');
}

// BASE: add groupe de contenu
if($_POST['operation']=='addContentGroup' && admin()){
	include ($operationsDir.'operations.addContentGroup.php');
}

// BASE: add contenu inline
if($_POST['operation']=='addContentInline' && admin()){
	include ($operationsDir.'operations.addContentInline.php');
}

// GENERIQUE: maj
if($_POST['operation']=='majBd' && admin()){
	include SCRIPTS.'connect_mysqli.php';
	$table=$mysqli->real_escape_string($_POST['table']);
	$champ=$mysqli->real_escape_string($_POST['champ']);
	$val=$mysqli->real_escape_string(stripslashes($_POST['val']));
	$id=intval($_POST['id']);

	if(!$table) $errTab[]="Table manquante";
	if(!$champ) $errTab[]="Champ manquant";
	// if(!$val) $errTab[]="Val manquante";
	if(!$id) $errTab[]="ID manquant";

	if(!sizeof($errTab)){
		$req="UPDATE `".$pbd.$table."` SET `$champ` = '$val' WHERE `".$pbd.$table."`.`id_".$table."` =$id LIMIT 1 ;";
		if(!$mysqli->query($req)) $erreur="Erreur BDD<br />".$req;
	}
	$mysqli->close();
}

// GENERIQUE: sup
if($_POST['operation']=='supBd' && admin()){
	include SCRIPTS.'connect_mysqli.php';
	$table=$mysqli->real_escape_string($_POST['table']);
	$id=intval($_POST['id']);

	$req="DELETE FROM `".$pbd.$table."` WHERE `".$pbd.$table."`.`id_".$table."` = $id LIMIT 1;";
	if(!$mysqli->query($req)) $erreur="Erreur BDD<br />".$req;
	$mysqli->close();
}

// ADMIN : MISE A JOUR DES CONTENUS
if($_POST['operation'] == 'majConts' && admin()){
	//
	$langueCont=$_POST['langueCont'];
	if(!file_exists("contenus/$langueCont.txt")) $erreur='Fichier de langue introuvable';
	else{
		$contenu='';
		// pour chaque var de contenu
		foreach($_POST as $key=>$val){
			if(substr($key, 0, 8) == 'contenu-'){
				$contenu .= '
#'.substr($key, 8).'
'.stripslashes($val);
			}
		}

		if(!$contenu) $erreur='Aucun contenu reçu';
		else{
			// echo $contenu;
			if($handle=fopen('contenus/'.$langueCont.'.txt', w)){
				fwrite($handle, $contenu);
				fclose($handle);
			}
			else $erreur='Ecriture impossible';
		}
	}
}
// ADMIN : NOUVELLE PAGE
if($_POST['operation'] == 'page_new' && admin()){
	include ($operationsDir.'operations.page_new.php');
}

// CONTACT
if($_POST['operation']=='contact'){
	include ($operationsDir.'operations.contact.php');
}
