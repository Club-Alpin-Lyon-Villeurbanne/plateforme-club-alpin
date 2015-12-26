<?php



	if(!allowed('comm_groupe_edit'))	$errTab[]='Vous n\'avez pas les droits nécessaires pour cette operation de gestion de groupe';
    
	$id_commission = intval($_GET['id_commission']);

	// CHECKIN VARS
	if(!sizeof($errTab)){
        
        include SCRIPTS.'connect_mysqli.php';
        $new_groupe = $_POST['new_groupe']; 
        if (isset($new_groupe) && is_array($new_groupe)) {
            foreach ($new_groupe as $groupe) {
                $id_comm = intval($groupe['id_commission']);
                $niveau_technique = intval($groupe['niveau_technique']);
                $niveau_physique = intval($groupe['niveau_physique']);
                $nom = $mysqli->real_escape_string(trim($groupe['nom']));
                $description= $mysqli->real_escape_string(trim($groupe['description']));
                if (empty($nom)) $errTab[] = 'Le nom du groupe est obligatoire';
                if ($id_comm != $id_commission) $errTab[] = 'Erreur de commission';
               
                if (!sizeof($errTab)) {
                    $req = 
                    "INSERT INTO `".$pbd."groupe` (`id`, `id_commission`, `nom`, `description`, `niveau_physique`, `niveau_technique`, `actif`) 
                        VALUES (NULL, '".$id_comm."', '".$nom."', '".$description."', '".$niveau_physique."', '".$niveau_technique."', '1');";
                    if(!$mysqli->query($req))	$errTab[]="Erreur SQL insertion groupe";
                }
            }
        }
        
        $groupes = $_POST['groupe']; 
        if (isset($groupes ) && is_array($groupes )) {
            foreach ($groupes as $groupe) {
                
                $id_groupe = intval($groupe['id']);
                $niveau_technique = intval($groupe['niveau_technique']);
                $niveau_physique = intval($groupe['niveau_physique']);
                $actif = intval($groupe['actif']);
                $nom = $mysqli->real_escape_string(trim($groupe['nom']));
                $description= $mysqli->real_escape_string(trim($groupe['description']));
                if (empty($groupe['nom'])) $errTab[] = 'Le nom du groupe est obligatoire';
               
                if (!sizeof($errTab)) {
                    $need_comma = false;
                    $req = "UPDATE `".$pbd."groupe` SET ";
                    if ($groupe['nom']) { 
                        $req .= "`nom` = '".$nom."' ";
                        $need_comma = true;
                    }
                    if ($groupe['description']) {
                        $req .= $need_comma?' , ':'';
                        $req .= " `description` = '".$description."' ";
                        $need_comma = true;
                    }
                    if (isset($groupe['niveau_technique'])) {
                        $req .= $need_comma?' , ':'';
                        $req .= "  `niveau_technique` = '".$niveau_technique."' ";
                        $need_comma = true;
                    }
                    if (isset($groupe['niveau_physique'])) {
                        $req .= $need_comma?' , ':'';
                        $req .= "  `niveau_physique` = '".$niveau_physique."' ";
                        $need_comma = true;
                    }
                    if (isset($groupe['actif'])) {
                        $req .= $need_comma?' , ':'';
                        $req .= "  `actif` = '".$actif."' ";
                        $need_comma = true;
                    }
                    $req .= "WHERE `id` = " . $id_groupe;
                    
                    if (isset($groupe['delete']) && $groupe['delete'] == 'on') {
                        $req = "DELETE FROM `".$pbd."groupe` WHERE `id` = " . $id_groupe;
                    }
                    
                    if(!$mysqli->query($req))	$errTab[]="Erreur SQL update / delete groupe : ".$req ;
                }
            }
        }
        
        
		$mysqli->close;

	}


?>