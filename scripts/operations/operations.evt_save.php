<?php
// continuons... Création de l'evt en lui meme
if(!sizeof($errTab)){

    // formatage des vars : la description héritée du RTE necessite un petit nettoyage de sécurité (javascript / WINcode...)
    include_once('tools/htmLawed/htmLawed.php');
    $description_evt=htmLawed($description_evt);

    // sécurisation BDD
    $titre_evt=$mysqli->real_escape_string($titre_evt);
    $tarif_evt=$mysqli->real_escape_string($tarif_evt);
    $tarif_detail=$mysqli->real_escape_string($tarif_detail);
    $repas_restaurant=$mysqli->real_escape_string($repas_restaurant);
    $tarif_restaurant=$mysqli->real_escape_string($tarif_restaurant);
    $massif_evt=$mysqli->real_escape_string($massif_evt);
    $rdv_evt=$mysqli->real_escape_string($rdv_evt);
    $tsp_evt=$mysqli->real_escape_string($tsp_evt);
    $tsp_end_evt=$mysqli->real_escape_string($tsp_end_evt);
    $tsp_evt_day=$mysqli->real_escape_string($tsp_evt_day);
    $tsp_evt_hour=$mysqli->real_escape_string($tsp_evt_hour);
    $tsp_end_evt_day=$mysqli->real_escape_string($tsp_end_evt_day);
    $tsp_end_evt_hour=$mysqli->real_escape_string($tsp_end_evt_hour);
    $matos_evt=$mysqli->real_escape_string($matos_evt);
    $itineraire=$mysqli->real_escape_string($itineraire);
    $difficulte_evt=$mysqli->real_escape_string($difficulte_evt);
    $description_evt=$mysqli->real_escape_string($description_evt);

    if($id_groupe == 0){
        $id_groupe = 'NULL';
    } else {
        $id_groupe=$mysqli->real_escape_string($id_groupe);
    }
    if ($tarif_evt == '0.00' || empty($tarif_evt)) {
        $tarif_evt = 'NULL';
    }
    if ($tarif_restaurant == '0.00' || empty($tarif_restaurant)) {
        $tarif_restaurant = 'NULL';
    }
    if ($distance_evt == '0.00' || empty($distance_evt)) {
        $distance_evt = 'NULL';
    }
    if ($denivele_evt == '0' || empty($denivele_evt)) {
        $denivele_evt = 'NULL';
    }

    // code : juste pour un formatage explicite des URL vers les sorties
    $code_evt=substr(formater($titre_evt, 3), 0, 30);


    if ($_POST['operation'] == 'evt_create') {
        $req="INSERT INTO ".$pbd."evt(id_evt ,status_evt ,status_legal_evt ,user_evt ,commission_evt ,tsp_evt ,tsp_end_evt ,tsp_crea_evt ,place_evt ,titre_evt ,code_evt ,massif_evt ,rdv_evt ,tarif_evt , tarif_detail, repas_restaurant, tarif_restaurant, denivele_evt ,distance_evt ,lat_evt ,long_evt ,matos_evt ,itineraire, difficulte_evt ,description_evt , need_benevoles_evt , join_start_evt, join_max_evt, ngens_max_evt, cycle_master_evt ,cycle_parent_evt ,child_version_from_evt ,child_version_tosubmit, id_groupe)
					VALUES (NULL , '0', '0', '$user_evt', '$commission_evt', '$tsp_evt', '$tsp_end_evt', '$tsp_crea_evt', '$place_evt', '$titre_evt', '$code_evt', '$massif_evt', '$rdv_evt', $tarif_evt, '$tarif_detail', '$repas_restaurant', $tarif_restaurant, $denivele_evt, $distance_evt, '$lat_evt', '$long_evt', '$matos_evt', '$itineraire', '$difficulte_evt', '$description_evt', $need_benevoles_evt , '$join_start_evt', '$join_max_evt', '$ngens_max_evt', '$cycle_master_evt', '$cycle_parent_evt', '0', '0', $id_groupe);";

    }
    elseif ($_POST['operation'] == 'evt_update') {
        // MISE A JOUR de l'éléments existant // IMPORTANT : le status repasse à 0
        $req="UPDATE ".$pbd."evt SET `status_evt`=0,
				`tsp_evt`='$tsp_evt',
				`tsp_end_evt` =  '$tsp_end_evt',
				`tsp_edit_evt` =  '$p_time',
				`titre_evt` =  '$titre_evt',
				`code_evt` =  '$code_evt',
				`massif_evt` =  '$massif_evt',
				`rdv_evt` =  '$rdv_evt',
				`tarif_evt` =  $tarif_evt,
				`tarif_detail` =  '$tarif_detail',
				`repas_restaurant` =  '$repas_restaurant',
				`tarif_restaurant` =  $tarif_restaurant,
				`denivele_evt` =  $denivele_evt,
				`distance_evt` =  $distance_evt,
				`lat_evt` =  '$lat_evt',
				`long_evt` =  '$long_evt',
				`matos_evt` =  '$matos_evt',
				`itineraire` =  '$itineraire',
				`difficulte_evt` =  '$difficulte_evt',
				`join_start_evt` =  '$join_start_evt',
				`join_max_evt` =  '$join_max_evt',
				`ngens_max_evt` =  '$ngens_max_evt',
				`description_evt` =  '$description_evt',
				`need_benevoles_evt` =  '$need_benevoles_evt'";
        if($id_groupe == 0){
            $req.=", id_groupe = 0 ";
        } else{
            $req.=", id_groupe = '$id_groupe' ";
        }
        if($cycle_master_evt == 0){
            $req.=", cycle_master_evt = 0 ";
        } else{
            $req.=", cycle_master_evt = '$cycle_master_evt' ";
        }
        if($cycle_parent_evt == 0){
            $req.=", cycle_parent_evt = 0 ";
        } else{
            $req.=", cycle_parent_evt = '$cycle_parent_evt' ";
            $req2 = "UPDATE ".$pbd."evt SET cycle_master_evt = 1 WHERE id_evt=$cycle_parent_evt";
            $mysqli->query($req2);
        }

        $req.=" WHERE  `caf_evt`.`id_evt` =$id_evt";
    }

    // on enregistre la sortie
    if(!$mysqli->query($req)) $errTab[]="Erreur SQL creation/update : ".$req;
    else{

        // jointures de l'ev avec les users spécifiés (encadrant, coenc' benev')

        if ($_POST['operation'] == 'evt_create') {
            $id_evt = $mysqli->insert_id;
        }
        
        $deja_encadrants = array();

        if ($_POST['operation'] == 'evt_update') {
            // suppression des inscrits si ils ont un role encadrant/coencadrant dans cette sortie
            // suppression des inscriptions précédentes encadrant/coencadrant/benevole

            $req = "SELECT * FROM " . $pbd . "evt_join WHERE evt_evt_join = $id_evt AND role_evt_join IN ('encadrant', 'coencadrant')";
            $results = $mysqli->query($req);
            if ($results) {
                while ($row = $results->fetch_assoc()) {
                    $deja_encadrants[] = $row['user_evt_join'];
                }
            }

            $new_encadrants = array_merge($encadrants, $coencadrants);
            foreach ($deja_encadrants as $id_encadrant) {
                if (in_array($id_encadrant, $new_encadrants)) {
                    // on ne touche pas, il reste avec son statut
                }
                // L'utilisateur n'a plus de statut co-encadrant, on le supprime
                else {
                    $req = "DELETE FROM " . $pbd . "evt_join WHERE evt_evt_join = $id_evt AND user_evt_join = $id_encadrant;";
                    if(!$mysqli->query($req)) $errTab[]="Erreur SQL au nettoyage des jointures";
                }
            }
        }

        if (!sizeof($errTab)) {
            foreach($encadrants as $id_user){
                $id_user=intval($id_user);
                if (!in_array($id_user, $deja_encadrants)) {
                    $req="INSERT INTO ".$pbd."evt_join(id_evt_join, status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(NULL , 1,               '$id_evt',  '$id_user',  'encadrant', $p_time);";
                    $mysqli->query($req);
                }
            }
            foreach($coencadrants as $id_user){
                $id_user=intval($id_user);
                if (!in_array($id_user, $deja_encadrants)) {
                    $req="INSERT INTO ".$pbd."evt_join(id_evt_join, status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(NULL , 1, '$id_evt',  '$id_user',  'coencadrant', $p_time);";
                    $mysqli->query($req);
                }
            }
            // Seulement en création :
            if ($_POST['operation'] == 'evt_create') {
                foreach($benevoles as $id_user){
                    $id_user=intval($id_user);
                    $req="INSERT INTO ".$pbd."evt_join(id_evt_join, status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join)
                                                        VALUES(NULL , 1, '$id_evt',  '$id_user',  'benevole', $p_time);";
                    $mysqli->query($req);
                }
            }
        }

        if (!sizeof($errTab)) {

            // Gestion des destinations
            if ($_POST['id_destination']) {

                $id_destination = $_POST['id_destination'];

                $req = "DELETE FROM " . $pbd . "evt_destination WHERE id_evt = $id_evt AND id_destination = $id_destination";
                if(!$mysqli->query($req)) $errTab[]="Erreur SQL de suppression de la jointure evt/destination ".$req;


                /// Sauvegarde des lieux :
                if (!$depose) {
                    $id_lieu_depose = $_POST['lieu']['id_lieu_depose'];
                } else {
                    $lieu_nom			= $mysqli->real_escape_string($depose['nom']);
                    $lieu_description	= $mysqli->real_escape_string($depose['description']);
                    $lieu_ign			= $mysqli->real_escape_string($depose['ign']);
                    $lieu_lat			= $mysqli->real_escape_string($depose['lat']);
                    $lieu_lng			= $mysqli->real_escape_string($depose['lng']);

                    $sql = "INSERT INTO `".$pbd."lieu` (`id`, `nom`, `description`, `ign`, `lat`, `lng`)
                            VALUES (NULL, '$lieu_nom', '$lieu_description', '$lieu_ign', '$lieu_lat', '$lieu_lng');";

                    if(!$mysqli->query($sql)) $errTab[]="Erreur SQL lors de la création du lieu de depose";
                    else {
                        $id_lieu_depose = $_POST['lieu']['depose']['id'] = $mysqli->insert_id;
                    }
                }

                if ($copy_depose_to_reprise) $id_lieu_reprise = $id_lieu_depose;
                else {
                    if (!$reprise) {
                        $id_lieu_reprise = $_POST['lieu']['id_lieu_reprise'];
                    } else {
                        $lieu_nom			= $mysqli->real_escape_string($reprise['nom']);
                        $lieu_description	= $mysqli->real_escape_string($reprise['description']);
                        $lieu_ign			= $mysqli->real_escape_string($reprise['ign']);
                        $lieu_lat			= $mysqli->real_escape_string($reprise['lat']);
                        $lieu_lng			= $mysqli->real_escape_string($reprise['lng']);

                        $sql = "INSERT INTO `".$pbd."lieu` (`id`, `nom`, `description`, `ign`, `lat`, `lng`)
                                VALUES (NULL, '$lieu_nom', '$lieu_description', '$lieu_ign', '$lieu_lat', '$lieu_lng');";

                        if(!$mysqli->query($sql)) $errTab[]="Erreur SQL lors de la création du lieu de reprise";
                        else{
                            $id_lieu_reprise = $_POST['lieu']['reprise']['id'] = $mysqli->insert_id;
                        }
                    }
                }

                if (!sizeof($errTab)) {

                    $date_depose = $mysqli->real_escape_string($_POST['lieu']['depose']['date_depose']);
                    $date_reprise = $mysqli->real_escape_string($_POST['lieu']['reprise']['date_reprise']);

                    $req = "INSERT INTO `".$pbd."evt_destination`
                                (`id`, `id_evt`, `id_destination`, `id_lieu_depose`, `date_depose`, `id_lieu_reprise`, `date_reprise`)
                           VALUES
                                (NULL, $id_evt, $id_destination, $id_lieu_depose, '$date_depose', $id_lieu_reprise, '$date_reprise');";

                    if(!$mysqli->query($req)) $errTab[]="Erreur SQL de jointure evt/destination ".$req;
                }

            }
        }

    }
}

$mysqli->close;



// All good
if(!sizeof($errTab)){
    // S'il ne s'agit pas d'un cycle :
    if(!$cycle_master_evt && !$cycle_parent_evt){
        // L'auteur de la sortie est redirigé vers son espace perso > ses sorties, avec un message "Attente de validation"
        header("Location:".$p_racine."profil/sorties/self.html?lbxMsg=evt_create_success");
    }
    // si cet evt est le premier d'un cycle, on reste sur la même page pour inciter à la création d'un nouvel événement dans ce cycle
    else{

        if ($_POST['operation']='evt_update') {
            unset ($id_evt);
            unset ($id_evt_to_update);
        }

        if($cycle_master_evt){
            // var pour le blocage de certaines options sur la page
            $suiteDeCycle = true;
            // message à afficher
            $lbxMsg='evt_create_success_newcycle';
            // on redirige vers la même page, avec des variables forcées
            $_POST['cycle']='child';
            $_POST['cycle_parent_evt']=$id_evt;
            // RAZ
            $_POST['tsp_evt_day']='';
            $_POST['tsp_evt_hour']='';
            $_POST['tsp_end_evt_day']='';
            $_POST['tsp_end_evt_hour']='';
            // modification du titre
            if(substr(strtoupper($_POST['titre_evt']), 0, 11) != 'SUITE DE : '){
                $_POST['titre_evt']='SUITE DE : '.$_POST['titre_evt'];
            }
        }
        // si cet evt est le N-ième d'un cycle, on reste sur la même page pour inciter à la création d'un nouvel événement dans ce cycle
        else{
            // var pour le blocage de certaines options sur la page
            $suiteDeCycle = true;
            // message à afficher
            $lbxMsg='evt_create_success_newcycle_2';
            // on redirige vers la même page, avec des variables forcées
            $_POST['cycle']='child';
            // RAZ
            $_POST['tsp_evt_day']='';
            $_POST['tsp_evt_hour']='';
            $_POST['tsp_end_evt_day']='';
            $_POST['tsp_end_evt_hour']='';
        }

        if ($_POST['operation']='evt_update') {
            session_id() || session_start();
            $_SESSION['POST_DATA_FROM_UPDATE'] = $_POST;
            header('Location:'.$p_racine.'creer-une-sortie/'.html_utf8($code_commission).'.html?lbxMsg='.$lbxMsg);
        }
    }
}