<?php

require_once __DIR__.'/../../vendor/autoload.php';

    // check user online
    if (!user()) {
        $errTab[] = "Vous avez été déconnecté. L'opération n'a pas été effectuée.";
    }

    // mise à jour infos texte
    if (!count($errTab)) {
        $id_user = (int) ($_SESSION['user']['id_user']);
        // $nickname_user=trim(stripslashes($_POST['nickname_user']));
        // $gender_user=trim(stripslashes($_POST['gender_user']));
        // $civ_user=trim(stripslashes($_POST['civ_user']));
        $tel_user = trim(stripslashes($_POST['tel_user']));
        $tel2_user = trim(stripslashes($_POST['tel2_user']));
        $birthday_user = trim(stripslashes($_POST['birthday_user']));
        $adresse_user = trim(stripslashes($_POST['adresse_user']));
        $cp_user = trim(stripslashes($_POST['cp_user']));
        $ville_user = trim(stripslashes($_POST['ville_user']));
        $pays_user = trim(stripslashes($_POST['pays_user']));
        $auth_contact_user = trim(stripslashes($_POST['auth_contact_user']));
        $email_user_mailchange = trim(stripslashes($_POST['email_user_mailchange']));

        if (!$id_user) {
            $errTab[] = 'Erreur technique : ID manquant';
        }
        if (!isMail($email_user_mailchange) && strlen($email_user_mailchange) > 0) {
            $errTab[] = 'Vous avez demandé à remplacer votre adresse e-mail, mais elle semble invalide.';
        }

        // 04/09/2013 - gmn - desactivation car import FFCAM => E.HENKE : on doit malgré tout pouvoir enregistrer les infos personnelles de contact
        if (!count($errTab)) {
            include SCRIPTS.'connect_mysqli.php';
            $auth_contact_user = $mysqli->real_escape_string($auth_contact_user);
            $req = 'UPDATE `'.$pbd."user`
				SET
				`auth_contact_user` = '$auth_contact_user'
				WHERE `id_user` =$id_user LIMIT 1 ;";

            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL';
            } else {
                user_login($_SESSION['user']['email_user'], false);
            }

            $mysqli->close();
        }
    }

    // mise à jour de la photo si transmise
    if (!count($errTab) && $_FILES['photo']['size'] > 0) {
        // CHECKS
        $allowedExts = ['jpg', 'jpeg', 'png'];
        $extension = strtolower(substr(strrchr($_FILES['photo']['name'], '.'), 1));
        if ((('image/jpeg' == $_FILES['photo']['type'])
            // || ($_FILES["photo"]["type"] == "image/png")
            || ('image/pjpeg' == $_FILES['photo']['type']))
            && ($_FILES['photo']['size'] < 41943040) // < 5Mo
            && in_array($extension, $allowedExts, true)) {
            if ($_FILES['photo']['error'] > 0) {
                $errTab[] = "Erreur dans l'image : ".$_FILES['photo']['error'];
            } else {
                // déplacement du fichier dans le dossier transit
                $uploaddir = 'ftp/transit/profil/';
                $i = 1;
                while (file_exists($uploaddir.$i.'-profil.jpg')) {
                    ++$i;
                }
                $filename = $i.'-profil.jpg';
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploaddir.$filename)) {
                    // REDIMS
                    include APP.'redims.php';
                    $size = getimagesize($uploaddir.$filename);

                    $rep_Dst = 'ftp/user/'.$id_user.'/';
                    $img_Dst = 'profil.jpg';
                    $rep_Src = $uploaddir;
                    $img_Src = $filename;

                    // créa du dossier s'il n'esxiste pas
                    if (!file_exists($rep_Dst) && $id_user) {
                        mkdir($rep_Dst);
                    }

                    // **** GRANDE
                    $W_fin = 1000;
                    $H_fin = 1000;
                    if (!fctredimimage($W_fin, $H_fin, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                        $errTab[] = 'Impossible de redimensionner la grande image';
                    }

                    // **** MINI
                    $img_Src = $rep_Dst.$filename;
                    $img_Dst = 'min-profil.jpg';

                    // REDIM ONLY
                    $W_fin = 150;
                    $H_fin = 150;

                    $rep_Dst = 'ftp/user/'.$id_user.'/';
                    $img_Dst = 'min-profil.jpg';
                    $rep_Src = 'ftp/user/'.$id_user.'/';
                    $img_Src = 'profil.jpg';

                    // redim
                    if (!fctredimimage($W_fin, $H_fin, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                        $errTab[] = 'Impossible de redimensionner la miniature';
                    }

                    // **** PICTO
                    $img_Src = 'min-profil.jpg';
                    $img_Dst = 'pic-profil.jpg';

                    // VERSION REDIM + CROP
                    // vars pour le redim/crop de la une
                    // <= crop
                    // >= redim
                    if ($size[0] / $size[1] <= 55 / 55) {
                        $W_fin = 55;
                        $H_fin = 0;
                    } else {
                        $W_fin = 0;
                        $H_fin = 55;
                    }
                    // redim
                    if (!fctredimimage($W_fin, $H_fin, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                        $errTab[] = 'Impossible de redimensionner l\'image (picto)';
                    }
                    // crop
                    $img_Src = $img_Dst;
                    $W_fin = 55;
                    $H_fin = 55;
                    if (!fctcropimage($W_fin, $H_fin, $rep_Dst, $img_Dst, $rep_Src, $img_Src)) {
                        $errTab[] = 'Impossible de croper l\'image (picto)';
                    }

                    /////////////
                    // suppression du fichier en standby
                    if (file_exists($uploaddir.$filename)) {
                        unlink(($uploaddir.$filename));
                    }
                } else {
                    $errTab[] = 'Erreur lors du déplacement du fichier';
                }
            }
        } else {
            $errTab[] = 'La photo doit être au format .jpg et peser moins de 5Mo !';
        }
    }

    // si mise à jour e-mail user
    if (strlen($email_user_mailchange) > 0) {
        include SCRIPTS.'connect_mysqli.php';
        $email_user_mailchange = $mysqli->real_escape_string($email_user_mailchange);

        // VERIFICATIONS
        // compte des entrées existantes avec cet e-mail
        $req = "SELECT COUNT(id_user) FROM caf_user WHERE email_user LIKE '$email_user_mailchange' AND id_user != $id_user ";
        $handleSql = $mysqli->query($req);
        if (getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM)) > 0) {
            $errTab[] = "Votre demande de modification d'e-mail est refusée : Un compte existe déjà avec cette adresse e-mail.";
        }

        // ENTRÉE DE LA DEMANDE DANS LA BD
        if (!count($errTab)) {
            $token = md5($p_time + rand(100, 999));
            $req = 'INSERT INTO `'.$pbd."user_mailchange` ( `id_user_mailchange` , `user_user_mailchange` , `token_user_mailchange` , `email_user_mailchange` )
														VALUES ('', 			'$id_user',				'$token', 				'$email_user_mailchange');";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL';
            } else {
                $id_user_mailchange = $mysqli->insert_id;
            }
        }

        $mysqli->close();

        // ENVOI DU MAIL
        if (!count($errTab)) {
            // check-in vars : string à retourner lors de la confirmation= md5 de la concaténation id-email
            $url = $p_racine.'email-change/'.$token.'-'.$id_user_mailchange.'.html';

            // content vars
            $subject = 'Modification de votre e-mail !';
            $content_header = '';
            $content_main = '
				<h1>'.$subject.'</h1>
				<p>Vous avez demandé à utiliser cette adresse e-mail comme identifiant. Cliquez sur le lien ci-dessous pour confirmer
				et vous pourrez utiliser cette adresse e-mail pour vous connecter au site et recevoir les notifications.
				Attention ce lien ne fonctionne que pendant une heure.</p>
				<p><a class="bigLink" href="'.$url.'" title="">'.$url.'</a></p>
				<p>Si vous n\'avez pas demandé à recevoir ce mail, il suffit de l\'ignorer.</p>
				';
            $content_footer = '';

            $mail = new CAFPHPMailer(); // defaults to using php "mail()"

            $mail->SetFrom($p_noreply, $p_sitename);
            $mail->AddAddress($email_user_mailchange, $_SESSION['user']['nickname_user']);
            $mail->Subject = $subject;
            //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
            $mail->setMailBody($content_main);
            $mail->setMailHeader($content_header);
            $mail->setMailFooter($content_footer);
            // $mail->setMailBody('TEST');
            // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

            // débug local
            if ('127.0.0.1' == $_SERVER['HTTP_HOST']) {
                $mail->IsMail();
            }

            if (!$mail->Send()) {
                $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter par téléphone pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
            }
        }
    }
