<?php

    include SCRIPTS.'connect_mysqli.php';

    // get datas
    $email_user = trim(stripslashes($_POST['email_user']));
    $mdp_user = trim(stripslashes($_POST['mdp_user']));

    if (!isset($errTab) || 0 === count($errTab)) {
        // **********
        // verification de l'existence ou non du compte
        $email_user_check = $mysqli->real_escape_string(htmlspecialchars($email_user, \ENT_NOQUOTES, 'UTF-8'));
        $req = 'SELECT COUNT(id_user) FROM '.$pbd."user WHERE email_user LIKE '$email_user_check' AND `valid_user`=1 LIMIT 1";
        $handleSql = $mysqli->query($req);
        // si le compte n'existe pas
        if (!getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM))) {
            $errTab[] = $fieldsErrTab['email_user'] = "Désolé, cette adresse email est inconnue ou pas encore validée !<br />Si vous êtes bien inscrit au Club Alpin, mais que ceci est votre première connexion sur ce site, vous devez d'abord créer votre compte.";
        }
    }
    if (!isset($errTab) || 0 === count($errTab)) {
        // **********
        // verification du mot de passe
        if ($use_md5_salt) {
            $mdp_user = md5($mdp_user.$md5_salt);
        } else {
            $mdp_user = md5($mdp_user);
        }
        $req = 'SELECT id_user, email_user, civ_user, lastname_user FROM '.$pbd."user WHERE email_user LIKE '$email_user_check' AND `valid_user`=1 AND `mdp_user` LIKE '$mdp_user' LIMIT 1";
        $handleSql = $mysqli->query($req);

        // si le compte existe
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // user_login($handle['id_user'], false); // false : pas de conn/deconn DB
            user_login($handle['email_user'], false); // false : pas de conn/deconn DB

            $successmsg = 'Bonjour '.html_utf8($handle['firstname_user']).". Vous êtes connecté.<br />
						Vous pouvez :<br />
						<a class='nice2 white' href='javascript:top.document.location.href=top.document.location.href' title=''><span class='bleucaf'>&gt;</span> Actualiser cette page</a><br />
						<a class='nice2 white' href='profil.html' title='' target='_top'><span class='bleucaf'>&gt;</span> Accéder à votre profil</a>";
        }
        if (!user()) {
            $errTab[] = $fieldsErrTab['mdp_user'] = 'Erreur : ce mot de passe est invalide.<br />Si vous avez oublié votre mot de passe, cliquez sur le lien sous le formulaire de connexion.';
        }
    }

    $mysqli->close();
