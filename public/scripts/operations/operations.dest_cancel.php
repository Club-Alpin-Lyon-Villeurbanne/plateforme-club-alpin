<?php

    $id_destination = (int) (substr(strrchr($p3, '-'), 1));
    $msg = trim(stripslashes($_POST['msg']));
    $nomadMsg = []; // message spécial par raport aux nomades

    // checks
    if (!strlen($msg)) {
        $errTab[] = 'Veuillez entrer un message';
    }
    if (!$id_destination) {
        $errTab[] = 'ID invalide';
    }

    // recuperation de la sortie demandée
    $destination = get_destination($id_destination);
include SCRIPTS.'connect_mysqli.php';

    // on a le droit d'annuler ?
    if (allowed('destination_supprimer')
        || $destination['id_user_who_create'] == $_SESSION['user']['id_user']
        || $destination['id_user_responsable'] == $_SESSION['user']['id_user']
        || $destination['id_user_adjoint'] == $_SESSION['user']['id_user']
    ) {
    } else {
        $errTab[] = 'Accès non autorisé';
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        $req = 'UPDATE `'.$pbd."destination` SET `annule` = '1' WHERE `id` = $id_destination;";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL annulation destination';
        }
    }

    // Mise à jour : annulation des sorties
    if (!isset($errTab) || 0 === count($errTab)) {
        $sorties = get_sorties_for_destination($id_destination);

        foreach ($sorties as $sortie) {
            $req = 'UPDATE '.$pbd."evt SET cancelled_evt='1', cancelled_who_evt='".(int) ($_SESSION['user']['id_user'])."', cancelled_when_evt='".$p_time."'  WHERE id_evt = ".$sortie['id_evt'];
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
        }
    }

        // message aux participants si la sortie est annulée alors qu'elle est publiée
        if ((!isset($errTab) || 0 === count($errTab)) && 1 == $destination['publie']) {
            // phpmailer
            require_once APP.'mailer'.DS.'class.phpmailer.caf.php';

            // contenu commun à chaque envoi
            $subject = 'Sorties du '.display_date($destination['date']).' annulées !';
            $content_main = "<h2>$subject</h2>
				<p>
					Les sorties du ".display_date($destination['date']).', destination
					&laquo;<i> '.html_utf8($destination['nom'])." </i>&raquo;
					viennent d'être annulées par <a href=\"".$p_racine.'voir-profil/'.(int) ($_SESSION['user']['id_user']).'.html">'.$_SESSION['user']['nickname_user'].'</a>.
					Voici le message joint :
				</p>
				<p>&laquo;<i> '.nl2br(html_utf8($msg))." </i>&raquo;</p>
				<p><a href='".$p_racine.'destination/'.html_utf8($destination['code']).'-'.(int) ($destination['id']).".html' title=''>&lt; Voir la page dédiée</a></p>
				";
            $content_header = '';
            $content_footer = '';

            $destination['joins'] = [];
            $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, nomade_user
                        , role_evt_join
                    FROM '.$pbd.'evt_join, '.$pbd."user
                    WHERE id_destination = $id_destination
                    AND user_evt_join = id_user
                    LIMIT 500";
            $handleSql2 = $mysqli->query($req);

            // desinscription des participants de la sortie
            if (!isset($errTab) || 0 === count($errTab)) {
                $req = "DELETE FROM caf_evt_join WHERE role_evt_join NOT IN ('encadrant', 'coencadrant') AND id_destination = $id_destination";
                if (!$mysqli->query($req)) {
                    $errTab[] = 'Erreur SQL (DELETE FROM caf_evt_join)';
                }
            }

            // PHPMAILER
            // ENVOI DU MAIL A CHACUN
            $mail = new CAFPHPMailer(); // defaults to using php "mail()"
            // $mail->AddReplyTo($p_noreply, 'Noreply');
            $mail->SetFrom($p_noreply, $p_sitename);
            $mail->Subject = $subject;
            //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
            $mail->setMailBody($content_main);
            $mail->setMailHeader($content_header);
            $mail->setMailFooter($content_footer);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                // debug nomades
                if (isMail($handle2['email_user'])) {
                    $mail->AddAddress($handle2['email_user'], $handle2['firstname_user'].' '.$handle2['lastname_user']);
                // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
                } else {
                    $nomadMsg[] = $handle2['civ_user'].' '.$handle2['firstname_user'].' '.$handle2['lastname_user'].' - '.$handle2['tel_user'].' - '.$handle2['tel2_user'];
                }
            }

            if (!$mail->Send()) {
                $errTab[] = "Échec à l'envoi du mail. Plus d'infos : ".($mail->ErrorInfo);
            }
        }

        $mysqli->close();

        // redirection vers la page de la sortie avec le message "annulé"
        if (!isset($errTab) || 0 === count($errTab)) {
            // sans message d'avertissement nomades
            if (!count($nomadMsg)) {
                header('Location:'.$p_racine.'destination/'.$destination['code'].'-'.$destination['id'].'.html');
            // echo 'nop';
            } else {
                header('Location:'.$p_racine.'destination/'.$destination['code_evt'].'-'.$destination['id'].'.html?lbxMsg=nomadMsg&nomadMsg='.(implode('****', $nomadMsg)));
            }
        }
