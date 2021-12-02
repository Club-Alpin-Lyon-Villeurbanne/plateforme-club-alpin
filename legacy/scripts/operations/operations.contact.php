<?php

$nom = strip_tags(stripslashes($_POST['nom']));
$email = strip_tags(stripslashes($_POST['email']));
$objet = strip_tags(stripslashes($_POST['objet']));
// on ne garde que les sauts de ligne en HTML
$message = nl2br(strip_tags(stripslashes($_POST['message'])));

// check antispam
if ('unlocked' != $_POST['lock']) {
    $errTab[] = "L'antispam n'a pas permis l'envoi du message. Merci d'envoyer le message en cliquant sur le bouton.";
}

if (strlen($nom) < 4) {
    $errTab[] = 'Veuillez entrer votre nom';
}
if (!isMail($email)) {
    $errTab[] = 'Veuillez entrer une adresse mail valide';
}
if (strlen($objet) < 4) {
    $errTab[] = 'Veuillez entrer un objet';
}
if (strlen($message) < 10) {
    $errTab[] = 'Veuillez entrer un message valide';
}

if (0 === count($errTab)) {
    // ENVOI DU MAIL
    // content vars
    $subject = $objet;
    $content_header = '';
    $content_main = '<h2>Ce message vous a été envoyé depuis votre site <i>'.$p_racine.'</i></h2>'
        .'<b>Infos :</b><table>'
        .($prenom ? '<tr><td><b>Prénom : </b></td><td>'.html_utf8($prenom)." </td></tr>\n" : '')
        .($nom ? '<tr><td><b>Nom : </b></td><td>'.html_utf8($nom)." </td></tr>\n" : '')
        .($ste ? '<tr><td><b>Société : </b></td><td>'.html_utf8($ste)." </td></tr>\n" : '')
        .($adresse ? '<tr><td><b>Adresse : </b></td><td>'.html_utf8($adresse)." </td></tr>\n" : '')
        .($tel ? '<tr><td><b>Tel : </b></td><td>'.html_utf8($tel)." </td></tr>\n" : '')
        .($ville ? '<tr><td><b>Prénom : </b></td><td>'.html_utf8($ville)." </td></tr>\n" : '')
        .($email ? '<tr><td><b>Email : </b></td><td><a href="mailto:'.html_utf8($email).'" title="">'.html_utf8($email).'</a> </td></tr>' : '')
        .($objet ? '<tr><td><b>Objet : </b></td><td>'.html_utf8($objet)." </td></tr>\n" : '')
        .'</table><br /><br />'
        .'<b>Message :</b><br />'.getUrlFriendlyString($message) // !!! cette variable doit déja etre sécurisée
        ;

    $content_footer = '';

    // PHPMAILER
    require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
    $mail = new CAFPHPMailer(); // defaults to using php "mail()"
    //$mail->CharSet = 'UTF-8';
    //$mail->IsHTML(true);
    // $mail->AddReplyTo($email, $nom);
    $mail->SetFrom($p_noreply, $p_sitename);
    $mail->AddAddress($p_contactdusite, 'Contact du site');
    $mail->Subject = $subject;
    //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
    $mail->setMailBody($content_main);
    $mail->setMailHeader($content_header);
    $mail->setMailFooter($content_footer);
    // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

    if (!$mail->Send()) {
        $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter par téléphone pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
    }

    // sauvegarde en BD
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
    $to_message = $mysqli->real_escape_string($p_contactdusite);
    $from_message = $mysqli->real_escape_string($email);
    $headers_message = ''; // obsolete
    $code_message = 'contact';
    $cont_message = $mysqli->real_escape_string($content_header."\n\n\n".$content_main."\n\n\n".$content_footer);
    $success_message = count($errTab) ? 0 : 1;
    $mysqli->query('INSERT INTO `'.$pbd."message` (`id_message` ,`date_message` ,`to_message` ,`from_message` ,`headers_message` ,`code_message` ,`cont_message` ,`success_message`)
            VALUES (NULL , '".time()."', '$to_message', '$from_message', '$headers_message', '$code_message', '$cont_message', '$success_message');");
    $mysqli->close();
}
// tout s'est bien passé, on vide les variables postées
if (0 === count($errTab)) {
    unset($_POST);
    $_POST['operation'] = 'contact';
}
