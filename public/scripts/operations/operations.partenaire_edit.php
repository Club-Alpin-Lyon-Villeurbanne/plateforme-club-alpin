<?php

$filename = null;

// PARAMS
$allowedExts = ['png']; // we need transparency
$uploaddir = 'ftp/partenaires/';

$partenaireTab = [];
$partenaireTab['part_id'] = (int) (trim(stripslashes($_POST['part_id'])));
$partenaireTab['part_order'] = (int) (trim(stripslashes($_POST['part_order'])));
$partenaireTab['part_type'] = (int) (trim(stripslashes($_POST['part_type'])));
$partenaireTab['part_enable'] = (int) (trim(stripslashes($_POST['part_enable'])));
$partenaireTab['part_name'] = trim(stripslashes($_POST['part_name']));
$partenaireTab['part_image'] = trim($_POST['part_image']);
$partenaireTab['part_desc'] = trim(stripslashes($_POST['part_desc']));
$partenaireTab['part_url'] = trim(stripslashes($_POST['part_url']));

// verification du format des donnees
if (!is_numeric($partenaireTab['part_id'])) {
    $errTab[] = 'id partenaire invalide';
}
if (!is_numeric($partenaireTab['part_order'])) {
    $errTab[] = 'ordre affichage partenaire invalide';
}
if (!is_numeric($partenaireTab['part_type'])) {
    $errTab[] = 'type de partenaire invalide';
}
if (!is_numeric($partenaireTab['part_enable'])) {
    $errTab[] = 'statut partenaire invalide';
}

if (strlen($partenaireTab['part_name']) < 2) {
    $errTab[] = "Merci d'entrer un nom valide";
}
if (strlen($partenaireTab['part_url']) < 5) {
    $errTab[] = "Merci d'entrer une url valide";
}
if (strlen($partenaireTab['part_desc']) < 2) {
    $errTab[] = "Merci d'entrer une description valide";
}
//	if(strlen($partenaireTab['part_image'])<4) 		$errTab[]="Merci d'entrer un nom d'image valide";

//	var_dump($errTab);

if (!isset($errTab) || 0 === count($errTab)) {
    if (!is_dir($uploaddir)) {
        mkdir($uploaddir);
    }
    // update SQL
    include $scriptsDir.'connect_mysqli.php';

    $partenaireTab['part_name'] = strtoupper(substr($partenaireTab['part_name'], 0, 50));
    $partenaireTab['part_url'] = substr($partenaireTab['part_url'], 0, 256);
    $partenaireTab['part_desc'] = substr($partenaireTab['part_name'], 0, 500);
    $new_part_image = trim(substr(formater($partenaireTab['part_name'], 3), 0, 70)).'.png';

    if (-1 == $partenaireTab['part_id']) {
        $partenaireTab['part_image'] = $new_part_image;
        $req = 'INSERT INTO `'.$pbd."partenaires` (part_name, part_order, part_image, part_desc, part_enable, part_url, part_type) VALUES (
            '".$mysqli->real_escape_string($partenaireTab['part_name'])."',
            '".$mysqli->real_escape_string($partenaireTab['part_order'])."',
            '".$mysqli->real_escape_string($partenaireTab['part_image'])."',
            '".$mysqli->real_escape_string($partenaireTab['part_desc'])."',
            '".$mysqli->real_escape_string($partenaireTab['part_enable'])."',
            '".$mysqli->real_escape_string($partenaireTab['part_url'])."',
            '".$mysqli->real_escape_string($partenaireTab['part_type'])."')";
    } else {
        if (0 !== strcmp($partenaireTab['part_image'], $new_part_image)) {
            // change name and file
            if (is_file($uploaddir.$partenaireTab['part_image'])) {
                error_log('rename partenaire image from '.$uploaddir.$partenaireTab['part_image'].' to '.$uploaddir.$new_part_image);
                rename($uploaddir.$partenaireTab['part_image'], $uploaddir.$new_part_image);
            }
            $partenaireTab['part_image'] = $new_part_image;
        }
        $req = 'UPDATE `'.$pbd."partenaires` SET
            part_name='".$mysqli->real_escape_string($partenaireTab['part_name'])."',
            part_order='".$mysqli->real_escape_string($partenaireTab['part_order'])."',
            part_image='".$mysqli->real_escape_string($partenaireTab['part_image'])."',
            part_desc='".$mysqli->real_escape_string($partenaireTab['part_desc'])."',
            part_enable='".$mysqli->real_escape_string($partenaireTab['part_enable'])."',
            part_url='".$mysqli->real_escape_string($partenaireTab['part_url'])."',
            part_type='".$mysqli->real_escape_string($partenaireTab['part_type'])."'";

        $req .= '	WHERE part_id='.$mysqli->real_escape_string($partenaireTab['part_id']);
    }

    //		error_log ($req);

    if (!$mysqli->query($req)) {
        $errTab[] = 'Erreur SQL : '.$mysqli->error;
    } else {
        if ($partenaireTab['part_id'] > 0) {
            $okTab[] = 'Mise à jour du partenaire OK';
        } else {
            $okTab[] = 'Création du partenaire OK';
        }

        mylog('operations.partenaire_edit', "ajout partenaire '".$mysqli->real_escape_string($partenaireTab['part_name'])."'");
    }
    /*
            error_log("type:".$_FILES['part_image']['type']);
            error_log("size:".$_FILES['part_image']['size']);
            error_log("name:".$_FILES['part_image']['name']);
            error_log("tmp_name:".$_FILES['part_image']['tmp_name']);
            error_log("error:".$_FILES['part_image']['error']);
    */
    if ($_FILES['part_image']['size'] > 0) {
        // CHECKS
        $extension = strtolower(substr(strrchr($_FILES['part_image']['name'], '.'), 1));
        if ((('image/png' == $_FILES['part_image']['type']) && in_array($extension, $allowedExts, true))) {
            if ($_FILES['photo']['error'] > 0) {
                $errTab[] = "Erreur dans l'image : ".$_FILES['part_image']['error'];
            } else {
                // deplacement du fichier dans le dossier partenaires
                if (is_file($uploaddir.$partenaireTab['part_image'])) {
                    //delete old file
                    unlink($uploaddir.$partenaireTab['part_image']);
                }
                if (!move_uploaded_file($_FILES['part_image']['tmp_name'], $uploaddir.$partenaireTab['part_image'])) {
                    $errTab[] = "Erreur lors du déplacement du fichier $uploaddir.$filename";
                }
            }
        }
    }

    $mysqli->close();
}
