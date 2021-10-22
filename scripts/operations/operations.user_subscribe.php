<?php

require_once __DIR__.'/../../vendor/autoload.php';

    $lastname_user = trim(stripslashes($_POST['lastname_user']));
    // $nickname_user=trim(stripslashes($_POST['nickname_user']));
    $cafnum_user = preg_replace('/\s+/', '', stripslashes($_POST['cafnum_user']));
    $email_user = strtolower(trim(stripslashes($_POST['email_user'])));
    $mdp_user = trim(stripslashes($_POST['mdp_user']));

    // d'abord, vérification du format des données
    // if(strlen($nickname_user)<5 || strlen($nickname_user)>20) 			$errTab[]="Merci d'entrer un pseudonyme de 5 à 20 caractères";
    // $tmp=preg_match($p_authchars,$nickname_user);
    // if(!empty($tmp)) $errTab[]=$fieldsErrTab['nickname_user']="Le surnom ne peut contenir que des chiffres, lettres, espaces et apostrophes";

    if (strlen($lastname_user) < 2) {
        $errTab[] = 'Merci de renseigner un nom de famille valide';
    }
    if (strlen($cafnum_user) != $limite_longeur_numero_adherent) {
        $errTab[] = "Merci de renseigner un numéro d'adhérent CAF valide ($limite_longeur_numero_adherent chiffres)";
    }
    if (!isMail($email_user)) {
        $errTab[] = 'Merci de renseigner une adresse e-mail valide';
    }
    if (strlen($mdp_user) < 6 || strlen($mdp_user) > 12) {
        $errTab[] = 'Le mot de passe doit faire de 6 à 12 caractères';
    }

    if (!count($errTab)) {
        include SCRIPTS.'connect_mysqli.php';
        // formatage sécurité
        $lastname_user = $mysqli->real_escape_string($lastname_user);
        // $nickname_user=$mysqli->real_escape_string($nickname_user);
        $cafnum_user = $mysqli->real_escape_string($cafnum_user);
        $email_user = $mysqli->real_escape_string($email_user);
        if ($use_md5_salt) {
            $mdp_user = md5($mdp_user.$md5_salt);
        } else {
            $mdp_user = md5($mdp_user);
        }

        // Si ce compte a été désactivé
        if (!count($errTab)) {
            $req = 'SELECT COUNT(id_user)
				FROM '.$pbd."user
				WHERE cafnum_user = '$cafnum_user'
				AND valid_user=2
				LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                $errTab[] = "Le compte lié à ce numéro d'adhérent a été désactivé manuellement par un responsable. Nous vous invitons à contacter le Président, ou vice-Président du club pour en savoir plus.";
            }
        }

        // Si ce compte est déjà existant et activé avec ce numéro de licence
        if (!count($errTab)) {
            $req = 'SELECT COUNT(id_user)
				FROM '.$pbd."user
				WHERE cafnum_user = '$cafnum_user'
				AND valid_user=1
				LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                $errTab[] = "Ce numéro d'adhérent correspond déjà à une inscription sur le site. Si vous avez perdu vos identifiants, utilisez le lien <i>Mot de passe oublié</i> ci-contre à droite.";
            }
        }

        // Si ce compte est déjà existant et activé avec cette adresse email
        if (!count($errTab)) {
            $req = 'SELECT COUNT(id_user)
				FROM '.$pbd."user
				WHERE email_user LIKE '$email_user'
				AND valid_user=1
				LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                $errTab[] = 'Cette adresse e-mail correspond déjà à une inscription sur le site. Si vous avez perdu vos identifiants, utilisez le lien <i>Mot de passe oublié</i> ci-contre à droite.';
            }
        }

        // vérification du numéro CAF
        if (!count($errTab)) {
            $req = 'SELECT COUNT(id_user) FROM '.$pbd."user WHERE cafnum_user = '$cafnum_user' LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if (!$row[0]) {
                $errTab[] = "Désolé, nous ne trouvons pas ce numéro d'adhérent dans notre base de donnée. Si vous venez de vous (ré)inscrire au CAF, nous vons invitons à réessayer ultérieurement.";
            }
        }

        // vérification de l'obsolescence du compte
        if (!count($errTab)) {
            $req = 'SELECT COUNT(id_user) FROM '.$pbd."user WHERE cafnum_user = '$cafnum_user' AND doit_renouveler_user =1 LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                $errTab[] = 'La licence pour ce compte semble être expirée. Si vous venez de renouveler votre licence nous vous invitons à réessayer ultérieurement.';
            }
        }

        // le nom colle ?
        if (!count($errTab)) {
            $id_user = false;
            $req = 'SELECT id_user
				FROM '.$pbd."user
				WHERE cafnum_user = '$cafnum_user'
				AND upper(lastname_user) LIKE '".strtoupper($lastname_user)."'
				ORDER BY id_user DESC
				LIMIT 1";
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $id_user = $row['id_user'];
            } // ID : clé permettenat l'enregistrement ci-après

            if (!$id_user) {
                $errTab[] = "Le nom que vous avez entré ne correspond pas au numéro d'adhérent dans notre base de données.";
            }
        }

        // création du pseudonyme
        if (!count($errTab)) {
            $nickname_user = false;
            // id_user défini juste avant
            $req = 'SELECT lastname_user, firstname_user
				FROM '.$pbd."user
				WHERE id_user = $id_user
				LIMIT 1";
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $firstname_user = ucfirst(mb_strtolower($row['firstname_user'], 'UTF-8'));
                $nickname_user = str_replace([' ', '-', '\''], '', $firstname_user.substr(strtoupper($row['lastname_user']), 0, 1));
                //				$nickname_user = str_replace(array(' ', '-', '\''), '', $row['firstname_user']) . substr($row['lastname_user'], 0, 1);
            }
            // secure SQL
            $nickname_user = $mysqli->real_escape_string($nickname_user);

            if (!$nickname_user) {
                $errTab[] = 'Impossible de générer le pseudo. Merci de nous contacter.';
            }
        }

        // tt ok ? activation
        // intégration des valeurs données et du token nécessaire à la confirmation par email
        if (!count($errTab)) {
            $cookietoken_user = md5($id_user.$p_time.rand(100, 999));
            $req = 'UPDATE '.$pbd."user SET email_user = '$email_user',
				mdp_user = '$mdp_user',
				nickname_user = '$nickname_user',
				created_user = '$p_time',
				cookietoken_user = '$cookietoken_user'
				WHERE id_user =$id_user LIMIT 1 ;";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur de sauvegarde';
            }
        }

        // envoi de l'e-mail
        if (!count($errTab)) {
            // check-in vars : string à retourner lors de la confirmation= md5 de la concaténation id-email
            $url = $p_racine.'user-confirm/'.$cookietoken_user.'-'.$id_user.'.html';

            // content vars
            $subject = 'Validez votre compte adhérent du '.$p_sitename;
            $content_header = '';
            $content_main = '
				<h1>'.$subject.'</h1>
				<p>
					Bonjour <i>'.$firstname_user.'</i>,<br /><br />Vous venez de créer votre compte adhérent
					sur le site du '.$p_sitename.'. Pour confirmer votre adresse e-mail et pouvoir vous connecter, cliquez
					sur le lien ci-dessous :
				</p>
				<p><a class="bigLink" href="'.$url.'">'.$url.'</a></p>
				<p>A tout de suite !</p>
				';
            $content_footer = '';

            $altcontent_main = 'Bonjour '.$firstname_user.', >Vous venez de créer votre compte adhérent
					sur le site du '.$p_sitename.'. Pour confirmer votre adresse e-mail et pouvoir vous connecter, copier le
					sur le lien ci-dessous dans votre navigateur internet : '.$url;

            $mail = new CAFPHPMailer(false, true); // defaults to using php "mail()"

            $mail->SetFrom($p_noreply, $p_sitename);
            $mail->AddAddress(stripslashes($email_user), stripslashes($nickname_user));
            $mail->Subject = $subject;
            //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)\nSinon copiez-coller le lien suivant dans votre navigateur:\n$url"; // optional, comment out and test
            $mail->setMailBody($content_main);
            $mail->setAltMailBody($altcontent_main);
            $mail->setMailHeader($content_header);
            $mail->setMailFooter($content_footer);
            // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

            if (!$mail->Send()) {
                $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter par téléphone pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
            }
        }

        $mysqli->close();
    }
