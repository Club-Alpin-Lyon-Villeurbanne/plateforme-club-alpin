<?php

$errTab = $errTabMail = [];
$id_transporteur = $id_destination = $destination = $encadrants = null;

if (!$_POST['transporteur']) {
    $errTab[] = 'Merci de sélectionner un transporteur.';
} else {
    $id_transporteur = trim(stripslashes($_POST['transporteur']));
}
if (!$_POST['id_destination'] || empty($_POST['id_destination'])) {
    $errTab[] = 'Identifiant destination manquant.';
} else {
    $id_destination = (int) ($_POST['id_destination']);
    $destination = get_destination($id_destination);
}
$mail_to_transporteur = false;
$mail_to_responsables = false;

if ('-1' != $id_transporteur) {
    $mail_to_transporteur = $id_transporteur;
    if (!$_POST['content_mail'] || empty($_POST['content_mail'])) {
        $errTab[] = 'Le contenu du mail ne peut être vide.';
    }
}

if ($_POST['transporteur'] || 'on' == $_POST['transporteur']) {
    $mail_to_responsables = true;
    $encadrants = get_all_encadrants_destination($id_destination, false);
}

if ($destination['mail']) {
    $errTab[] = 'Les emails ont déjà été envoyés.';
}

if (0 === count($errTab)) {
    // ENVOI DU MAIL

    require_once APP.'mailer'.DS.'class.phpmailer.caf.php';

    if ($mail_to_transporteur) {
        $toMail = $p_transporteurs[$mail_to_transporteur]['email'];
        $toName = $p_transporteurs[$mail_to_transporteur]['nom'];

        // contenu
        $subject = 'Nouvelle sortie du '.$p_sitename;
        $content_main = "<h2>$subject</h2>";
        $content_main .= nl2br('<p>'.$_POST['content_mail'].'</p>');

        $content_header = '';
        $content_footer = '';

        $mail = new CAFPHPMailer(); // defaults to using php "mail()"

        $mail->SetFrom($p_noreply, $p_sitename);
        $mail->Subject = $subject;
        //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
        $mail->setMailBody($content_main);
        $mail->setMailHeader($content_header);
        $mail->setMailFooter($content_footer);
        $mail->AddAddress($toMail, $toName);
        // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

        if (!$mail->Send()) {
            $errTabMail[] = "Échec à l'envoi du mail à ".html_utf8($toName).". Plus d'infos : ".($mail->ErrorInfo);
        }
    }

    if (!$errTabMail && $mail_to_responsables) {
        foreach ($encadrants as $encadrant) {
            // recup de son email & nom
            $toMail = $encadrant['email_user'];
            $toName = $encadrant['firstname_user'];
            if (!isMail($toMail)) {
                $errTabMail[] = "Les coordonnées du contact $toMail sont erronées. Il ne sera pas alerté.";
            }

            // recup infos evt
            $evtUrl = '';
            $evtName = '';
            $evtUrl = html_utf8($p_racine.'sortie/'.$encadrant['sortie']['code_evt'].'-'.$encadrant['sortie']['id_evt'].'.html');
            $evtName = html_utf8($encadrant['sortie']['titre_evt']);

            $evtFiche = html_utf8($p_racine.'feuille-de-sortie/evt-'.$encadrant['sortie']['id_evt'].'.html');
            $destFiche = html_utf8($p_racine.'feuille-de-sortie/dest-'.$id_destination.'.html');

            if (0 === count($errTabMail)) {
                // contenu
                $subject = "Vous êtes responsable d'une sortie à venir du CAF";
                $content_main = "<h2>$subject</h2>
						<p>
							Bonjour $toName,<br />
							Vous êtes (co-)encadrant de la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;. Les inscriptions ont été cloturées.
						</p>
						<p>
							Cliquez sur le lien ci-dessous pour en savoir plus :<br />
							<a href='$evtUrl'>$evtUrl</a> et imprimez au besoin <a href='$evtFiche'>la fiche de sortie</a> et <a href='$destFiche'>la fiche de destination</a>.<br />
							<br />
							Bonne journée.
						</p>
						<ul>
                            <li>'$evtFiche'</li>
                            <li>'$destFiche'</li>
                        </ul>
					";

                $content_header = '';
                $content_footer = '';

                $mail = new CAFPHPMailer(); // defaults to using php "mail()"

                $mail->SetFrom($p_noreply, $p_sitename);
                $mail->Subject = $subject;
                //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
                $mail->setMailBody($content_main);
                $mail->setMailHeader($content_header);
                $mail->setMailFooter($content_footer);
                $mail->AddAddress($toMail, $toName);
                // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

                if (!$mail->Send()) {
                    $errTabMail[] = "Échec à l'envoi du mail à ".html_utf8($toMail).". Plus d'infos : ".($mail->ErrorInfo);
                }
            }
        }
    }

    if (count($errTabMail)) {
        $errTab = array_merge($errTabMail, $errTab);
    }

    if (0 === count($errTab)) {
        include SCRIPTS.'connect_mysqli.php';
        global $userAllowedTo, $pbd;
        $req = 'UPDATE `'.$pbd."destination` SET `mail` = '1' WHERE `caf_destination`.`id` = $id_destination";
        if (!$mysqli->query($req)) {
            $errTab[] = "Les emails ont bien été envoyé, mais cette information n'a pas été enregistrée";
        }
        $mysqli->close();
    }
}
