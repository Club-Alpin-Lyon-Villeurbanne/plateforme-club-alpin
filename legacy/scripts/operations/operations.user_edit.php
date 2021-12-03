<?php

global $kernel;

$userTab = [];
$userTab['cafnum_user'] = trim(stripslashes($_POST['cafnum_user']));
$userTab['cafnum_user_new'] = trim(stripslashes($_POST['cafnum_user_new']));
$userTab['email_user'] = trim(stripslashes($_POST['email_user']));
$userTab['mdp_user'] = trim(stripslashes($_POST['mdp_user']));
$userTab['id_user'] = trim(stripslashes($_POST['id_user']));
$userTab['auth_contact_user'] = trim(stripslashes($_POST['auth_contact_user']));
$userTab['lastname_user'] = trim(stripslashes($_POST['lastname_user']));

/*
// CHAMPS AUTO FFCAM
$userTab['nickname_user'] = trim(stripslashes($_POST['nickname_user']));
$userTab['civ_user'] =trim(stripslashes($_POST['civ_user']));
$userTab['firstname_user'] =trim(stripslashes($_POST['firstname_user']));
$userTab['birthday_user'] =trim(stripslashes($_POST['birthday_user']));
$userTab['tel_user'] =trim(stripslashes($_POST['tel_user']));
$userTab['tel2_user'] =trim(stripslashes($_POST['tel2_user']));
$userTab['adresse_user'] =trim(stripslashes($_POST['adresse_user']));
$userTab['cp_user'] =trim(stripslashes($_POST['cp_user']));
$userTab['ville_user'] =trim(stripslashes($_POST['ville_user']));
$userTab['pays_user'] =trim(stripslashes($_POST['pays_user']));
*/

// vérification du format des données
if (!isMail($userTab['email_user'])) {
    $errTab[] = 'Adresse e-mail invalide';
}
if ('' !== $userTab['mdp_user'] && (strlen($userTab['mdp_user']) < 6 || strlen($userTab['mdp_user']) > 12)) {
    $errTab[] = 'Le mot de passe doit faire de 6 à 12 caractères';
}

if (is_numeric(1 != $userTab['cafnum_user']) || 12 != strlen($userTab['cafnum_user'])) {
    $errTab[] = "Numéro d'adhérent invalide : ".$userTab['cafnum_user'];
}
if (0 != strlen($userTab['cafnum_user_new']) && ((!is_numeric($userTab['cafnum_user_new'])) || 12 != strlen($userTab['cafnum_user_new']))) {
    $errTab[] = "Nouveau numéro d'adhérent invalide : ".$userTab['cafnum_user_new'];
}

/*
if(strlen($userTab['civ_user'])<1) 		$errTab[]="Merci d'entrer la civilité";
if(strlen($userTab['firstname_user'])<2) 	$errTab[]="Merci d'entrer un prenom valide";
if(strlen($userTab['lastname_user'])<2) 	$errTab[]="Merci d'entrer un nom de famille valide";
if(strlen($userTab['nickname_user'])<5 || strlen($userTab['nickname_user'])>20) 			$errTab[]="Merci d'entrer un pseudonyme de 5 à 20 caractères";
    $tmp=preg_match($p_authchars,$userTab['nickname_user']);
    if(!empty($tmp)) $errTab[]=$fieldsErrTab['nickname_user']="Le surnom ne peut contenir que des chiffres, lettres, espaces et apostrophes";
// date de naissance :
if(!preg_match("#[0-9]{2}/[0-9]{2}/[0-9]{4}#", $userTab['birthday_user'])) $errTab[]="La date de naissance doit être au format jj/mm/aaaa.";
if($userTab['mdp_user'] != $_POST['mdp_user_confirm']) 			$errTab[]="Veuillez entrer deux fois le même mot de passe, sans espace";

// formatage date anniversaire
if(!sizeof($errTab)){
    // tsp de début
    $tab=explode('/', $userTab['birthday_user']);
    $userTab['birthday_user']=mktime(0, 0, 0, $tab[1], $tab[0], $tab[2]);
}
*/
if (!isset($errTab) || 0 === count($errTab)) {
    // insertion SQL
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    // securisation des vars
    /*
            $userTab['civ_user']=$mysqli->real_escape_string($userTab['civ_user']);
            $userTab['firstname_user']=$mysqli->real_escape_string($userTab['firstname_user']);
            $userTab['nickname_user']=$mysqli->real_escape_string($userTab['nickname_user']);
            $userTab['birthday_user']=$mysqli->real_escape_string($userTab['birthday_user']);
            $userTab['tel_user']=$mysqli->real_escape_string($userTab['tel_user']);
            $userTab['tel2_user']=$mysqli->real_escape_string($userTab['tel2_user']);
            $userTab['adresse_user']=$mysqli->real_escape_string($userTab['adresse_user']);
            $userTab['cp_user']=$mysqli->real_escape_string($userTab['cp_user']);
            $userTab['ville_user']=$mysqli->real_escape_string($userTab['ville_user']);
            $userTab['pays_user']=$mysqli->real_escape_string($userTab['pays_user']);
    */
    if ($userTab['cafnum_user_new'] > 0) {
        // echange du numero d'adherent,
        $req = "UPDATE `caf_user` SET cafnum_user='".$mysqli->real_escape_string($userTab['cafnum_user'])."' WHERE cafnum_user='".$mysqli->real_escape_string($userTab['cafnum_user_new'])."' AND lastname_user='".$mysqli->real_escape_string($userTab['lastname_user'])."'";
        $okTab[] = "Changement du numéro d'adhérent (".$userTab['cafnum_user'].' -> '.$userTab['cafnum_user_new']."), la mise à jour de la date d'adhésion sera effective sous 24h.";
        if (!$mysqli->query($req)) {
            $errTab[] = "Echange du numéro d'adhérent en erreur : ".$mysqli->error;
        } else {
            $userTab['cafnum_user'] = $userTab['cafnum_user_new'];
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "UPDATE `caf_user` SET email_user='".$mysqli->real_escape_string($userTab['email_user'])."',
            auth_contact_user='".$mysqli->real_escape_string($userTab['auth_contact_user'])."',
            cafnum_user='".$mysqli->real_escape_string($userTab['cafnum_user'])."'";

        $req .= "	WHERE id_user='".$mysqli->real_escape_string($userTab['id_user'])."'";

        if (!$mysqli->query($req)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $req,
            ]);
            $errTab[] = 'Erreur SQL';
        } else {
            $okTab[] = 'Mise à jour du compte';
        }
    }
}
