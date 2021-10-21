<?php

    $civ_user = trim(stripslashes($_POST['civ_user']));
    $firstname_user = trim(stripslashes($_POST['firstname_user']));
    $lastname_user = trim(stripslashes($_POST['lastname_user']));
    $nickname_user = trim(stripslashes($_POST['nickname_user']));
    $cafnum_user = trim(stripslashes($_POST['cafnum_user']));
    $email_user = trim(stripslashes($_POST['email_user']));
    $mdp_user = trim(stripslashes($_POST['mdp_user']));
    $birthday_user = trim(stripslashes($_POST['birthday_user']));
    $tel_user = trim(stripslashes($_POST['tel_user']));
    $tel2_user = trim(stripslashes($_POST['tel2_user']));
    $adresse_user = trim(stripslashes($_POST['adresse_user']));
    $cp_user = trim(stripslashes($_POST['cp_user']));
    $ville_user = trim(stripslashes($_POST['ville_user']));
    $pays_user = trim(stripslashes($_POST['pays_user']));
    $auth_contact_user = trim(stripslashes($_POST['auth_contact_user']));

    // vérification du format des données
    if (strlen($civ_user) < 1) {
        $errTab[] = "Merci d'entrer la civilité";
    }
    if (strlen($firstname_user) < 2) {
        $errTab[] = "Merci d'entrer un prenom valide";
    }
    if (strlen($lastname_user) < 2) {
        $errTab[] = "Merci d'entrer un nom de famille valide";
    }
    if (strlen($nickname_user) < 5 || strlen($nickname_user) > 20) {
        $errTab[] = "Merci d'entrer un pseudonyme de 5 à 20 caractères";
    }
        $tmp = preg_match($p_authchars, $nickname_user);
        if (!empty($tmp)) {
            $errTab[] = $fieldsErrTab['nickname_user'] = 'Le surnom ne peut contenir que des chiffres, lettres, espaces et apostrophes';
        }
    if (!isMail($email_user)) {
        $errTab[] = 'Adresse e-mail invalide';
    }
    if (strlen($mdp_user) < 6 || strlen($mdp_user) > 12) {
        $errTab[] = 'Le mot de passe doit faire de 6 à 12 caractères';
    }
    if ($mdp_user != $_POST['mdp_user_confirm']) {
        $errTab[] = 'Veuillez entrer deux fois le même mot de passe, sans espace';
    }
    // date de naissance :
    if (!preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $birthday_user)) {
        $errTab[] = 'La date de naissance doit être au format jj/mm/aaaa.';
    }

    // formatage date anniversaire
    if (!count($errTab)) {
        // tsp de début
        $tab = explode('/', $birthday_user);
        $birthday_user = mktime(0, 0, 0, $tab[1], $tab[0], $tab[2]);
    }

    if (!count($errTab)) {
        // insertion SQL
        include SCRIPTS.'connect_mysqli.php';

        // securisation des vars
        $civ_user = $mysqli->real_escape_string($civ_user);
        $firstname_user = $mysqli->real_escape_string($firstname_user);
        $lastname_user = $mysqli->real_escape_string($lastname_user);
        $nickname_user = $mysqli->real_escape_string($nickname_user);
        $cafnum_user = $mysqli->real_escape_string($cafnum_user);
        $email_user = $mysqli->real_escape_string($email_user);
        if ($use_md5_salt) {
            $mdp_user = md5($mdp_user.$md5_salt);
        } else {
            $mdp_user = md5($mdp_user);
        }
        $birthday_user = $mysqli->real_escape_string($birthday_user);
        $tel_user = $mysqli->real_escape_string($tel_user);
        $tel2_user = $mysqli->real_escape_string($tel2_user);
        $adresse_user = $mysqli->real_escape_string($adresse_user);
        $cp_user = $mysqli->real_escape_string($cp_user);
        $ville_user = $mysqli->real_escape_string($ville_user);
        $pays_user = $mysqli->real_escape_string($pays_user);
        $auth_contact_user = $mysqli->real_escape_string($auth_contact_user);

        // vérification anti doublon (seulement sur comptes confirmés)
        $req = "SELECT COUNT(id_user) FROM caf_user WHERE email_user LIKE '$email_user' AND valid_user=1";
        $result = $mysqli->query($req);
        $row = $result->fetch_row();
        if ($row[0]) {
            $errTab[] = 'Un compte validé existe déjà avec cette adresse e-mail. Avez-vous <a href="includer.php?p=pages/mot-de-passe-perdu.php" class="fancyframe" title="">oublié le mot de passe ?</a>';
        } else {
            $req = "INSERT INTO `caf_user` (`id_user`, `email_user`, `mdp_user`, `cafnum_user`, `firstname_user`, `lastname_user`, `nickname_user`, `created_user`, `birthday_user`, `tel_user`, `tel2_user`, `adresse_user`, `cp_user`, `ville_user`, `pays_user`, `civ_user`, `moreinfo_user`, `auth_contact_user`, `valid_user`, `cookietoken_user`, `manuel_user`)
								VALUES (NULL, '$email_user', '$mdp_user', '$cafnum_user', '$firstname_user', '$lastname_user', '$nickname_user', '$p_time', '$birthday_user', '$tel_user', '$tel2_user', '$adresse_user', '$cp_user', '$ville_user', '$pays_user', '$civ_user', '', '$auth_contact_user', '1', '', '1');";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
        }
        $mysqli->close();
    }
