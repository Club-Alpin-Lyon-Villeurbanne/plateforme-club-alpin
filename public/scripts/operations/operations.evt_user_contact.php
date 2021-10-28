<?php

    $id_evt = (int) ($_POST['id_evt']);
    $user_evt = (int) ($_SESSION['user']['id_user']);
    $objet = trim(stripslashes($_POST['objet']));
    $message = trim(stripslashes($_POST['message']));

    switch ($_POST['status_sendmail'].'') {
        case '*':
            $status_sendmail = false; break;
        case '0':
        case '1':
        case '2':
            $status_sendmail = (int) ($_POST['status_sendmail']); break;
        default:
            $errTab[] = 'Merci de sélectionner les destinataires du message.';
    }

    // check
    if (!$id_evt) {
        $errTab[] = 'Missing evt id';
    }
    if (strlen($objet) < 4) {
        $errTab[] = 'Veuillez entrer un objet de plus de 4 caractères';
    }
    if (strlen($message) < 10) {
        $errTab[] = 'Veuillez entrer un message valide';
    }

    include SCRIPTS.'connect_mysqli.php';

    if (!isset($errTab) || 0 === count($errTab)) {
        // sélection de l'événement, avec vérification que j'EN SUIS L'AUTEUR, puis des users liés en fonction des destinataires demandés
        $req = "
		SELECT  `id_user` ,  `email_user` ,  `firstname_user` ,  `lastname_user` ,  `nickname_user` ,  `civ_user`
		FROM caf_user, caf_evt_join, caf_evt
		WHERE id_evt= $id_evt
		AND user_evt_join =id_user
		AND evt_evt_join =id_evt

		".(false !== $status_sendmail ? " AND status_evt_join =$status_sendmail " : '');

        //		print ($req);exit;
        $destTab = [];
        $handleSql = $mysqli->query($req);

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $destTab[] = $handle;
        }

        if (!count($destTab)) {
            $errTab[] = 'Aucun destinataire trouvé. Vérifiez la liste de destinataires choisie.';
        }
    }

    // créa, envoi du mail
    if (!isset($errTab) || 0 === count($errTab)) {
        // infos evt
        $req = "SELECT * FROM caf_evt WHERE id_evt = $id_evt";
        $handleSql = $mysqli->query($req);
        if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $titre_evt = $handle['titre_evt'];
            $code_evt = $handle['code_evt'];
            $id_evt = $handle['id_evt'];
            $tsp_evt = $handle['tsp_evt'];
        }

        $subject = $objet;
        $content_header = '';
        $content_main = '<h2>Bonjour !</h2>
			<p>Vous avez reçu un message de '.html_utf8($_SESSION['user']['firstname_user']).' '.html_utf8($_SESSION['user']['lastname_user']).' au sujet de la sortie
				<a href="'.$p_racine.'sortie/'.$code_evt.'-'.$id_evt.'.html">'.html_utf8($titre_evt).'</a></p>'
            .'<p><b>Objet :</b><br />'.html_utf8($subject).'<br />&nbsp;</p>'
            .'<p><b>Message :</b><br />'.nl2br(getUrlFriendlyString(html_utf8($message))).'<br />&nbsp;</p>'
            ;

        $content_footer = '';

        // PHPMAILER
        require_once APP.'mailer'.DS.'class.phpmailer.caf.php';
        $mail = new CAFPHPMailer(); // defaults to using php "mail()"

        $mail->AddReplyTo($_SESSION['user']['email_user'] ?: $p_noreply);
        $mail->SetFrom($p_noreply, $p_sitename);
        $mail->Subject = $subject;
        //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
        $mail->setMailBody($content_main);
        $mail->setMailHeader($content_header);
        $mail->setMailFooter($content_footer);

        foreach ($destTab as $destinataire) {
            $mail->AddBCC($destinataire['email_user'], $destinataire['firstname_user'].' '.$destinataire['lastname_user']);
        }

        if (!$mail->Send()) {
            $errTab[] = "Échec à l'envoi du mail à ".$destinataire['email_user'].". Plus d'infos : ".($mail->ErrorInfo);
        }
    }
    $mysqli->close();

    // reset vals
    if (!isset($errTab) || 0 === count($errTab)) {
        $_POST['objet'] = $_POST['message'] = '';
    }
