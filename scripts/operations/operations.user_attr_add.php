<?php

	include SCRIPTS.'connect_mysqli.php';
	$needComm=false; // besoin, ou pas de spécifier la commission liée à ce type

	// Vérification des variables données
	$id_usertype=intval($_POST['id_usertype']);
	$id_user=intval($_POST['id_user']);
	$params_user_attr_tab=$_POST['commission'];
	if(!$id_usertype || !$id_user) $errTab[]="Valeurs manquantes";

	/* checks manuels par rapport aux ID des droits exportés depuis la BDD
	|1|0|visiteur|Visiteur|0
	|2|10|adherent|Adhérent|0
	|3|40|redacteur|Rédacteur|1
	|4|60|encadrant|Encadrant|1
	|5|70|responsable-commission|Resp. de commission|1
	|6|90|president|Président|0
	|7|80|vice-president|Vice Président|0
	|8|100|administrateur|Administrateur|0
	|9|20|salarie|Salarié|0
	|10|30|benevole|Bénévole|1
	|11|50|coencadrant|Co-encadrant|1
	*/

	// user_giveright_1
	if($id_usertype==10 && !allowed('user_giveright_1')) $errTab[]="Vous ne disposez pas des droits nécessaires pour attribuer ce statut";
	if($id_usertype==11 && !allowed('user_giveright_1')) $errTab[]="Vous ne disposez pas des droits nécessaires pour attribuer ce statut";
	if($id_usertype==4 && !allowed('user_giveright_1')) $errTab[]="Vous ne disposez pas des droits nécessaires pour attribuer ce statut";

	// user_giveright_2
	if($id_usertype==9 && !allowed('user_giveright_2')) $errTab[]="Vous ne disposez pas des droits nécessaires pour attribuer ce statut";

	// user_givepresidence
	if($id_usertype==6 && !allowed('user_givepresidence')) $errTab[]="Vous ne disposez pas des droits nécessaires pour attribuer ce statut";
	if($id_usertype==7 && !allowed('user_givepresidence')) $errTab[]="Vous ne disposez pas des droits nécessaires pour attribuer ce statut";

	if(!sizeof($errTab)){
		// Vérification dans la liste des types
		// + Ce type a t-il besoin de paramètres pour fonctionner ?
		$req="SELECT * FROM ".$pbd."usertype WHERE id_usertype =$id_usertype LIMIT 1";
		$result = $mysqli->query($req);

		// trouvé
		if(!$result->num_rows) $errTab[]="Aucune entree de ce type";
		else{
			while($row = $result->fetch_assoc())
				$needComm=$row['limited_to_comm_usertype'];
		}
	}

	// a t-on bien joint des paramètres ?
	if(!sizeof($errTab) && $needComm){
		if(!sizeof($params_user_attr_tab)) $errTab[]="Vous devez spécifier au moins une commission pour ce statut.";
	}

	// allez, enfin on intègre
	if(!sizeof($errTab)){
		if(!$needComm) $params_user_attr_tab=array('');
		// pour chaque commission
		foreach($params_user_attr_tab as $params_user_attr){
			$params_user_attr=$mysqli->real_escape_string($params_user_attr);

			// Cet attribut avec ces paramètres n'existe t-il pas déjà pour cet user ?
			$req="SELECT COUNT(id_user_attr)
				FROM ".$pbd."user_attr
				WHERE user_user_attr=$id_user
				AND usertype_user_attr=$id_usertype
				AND params_user_attr LIKE '$params_user_attr' LIMIT 1";
			$result = $mysqli->query($req);
			$row = $result->fetch_row();
			if(!$row[0]){
				// ajout
				$req="INSERT INTO ".$pbd."user_attr(id_user_attr ,user_user_attr ,usertype_user_attr ,params_user_attr ,details_user_attr)
											VALUES (NULL , '$id_user', '$id_usertype', '$params_user_attr', '$p_time');";
				if(!$mysqli->query($req)) $errTab[]="Erreur SQL";
			}
		}
	}

	$mysqli->close();

	// et si on modifie ses propres données, rechargeons donc notre session
	user_login($_SESSION['user']['email_user']);

	// log admin
	if(!sizeof($errTab)) mylog("user_attr_add", "Attribution d'un nouveau droit (id=$id_usertype) à un user (id=$id_user)");

?>