<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

$destinataire = $expediteur = null;

$id_user = (int) ($_POST['id_user']);
$nom = trim(strip_tags(stripslashes($_POST['nom'])));
$email = trim(strip_tags(stripslashes($_POST['email'])));
$objet = trim(strip_tags(stripslashes($_POST['objet'])));
$message = trim(nl2br(strip_tags(stripslashes($_POST['message'])))); // on ne garde que les sauts de ligne en HTML

if (strlen($objet) < 4) {
    $errTab[] = 'Veuillez entrer un objet de plus de 4 caractères';
}
if (strlen($message) < 10) {
    $errTab[] = 'Veuillez entrer un message valide';
}

// vérifications si contact non-user
if (!user()) {
    if (strlen($nom) < 4) {
        $errTab[] = 'Veuillez entrer votre nom';
    }
    if (!isMail($email)) {
        $errTab[] = 'Veuillez entrer une adresse mail valide';
    }
}
// récupération des infos si contact user
else {
    $expediteur = false;
    // ce user autorise t-il le contact
    $req = 'SELECT id_user, civ_user, firstname_user, lastname_user, email_user, nickname_user
        FROM caf_user
        WHERE id_user = '.getUser()->getIdUser();

    $handleSql = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $expediteur = $handle;
    }
    if (!$expediteur) {
        $errTab[] = 'Expediteur introuvable';
    }

    // dans ce cas, les valeurs sont réécrites
    $nom = $expediteur['civ_user'].' '.$expediteur['firstname_user'].' '.$expediteur['lastname_user'].' ('.$expediteur['nickname_user'].')';
    $email = $expediteur['email_user'];
}

// récup' infos destinataire
if (!isset($errTab) || 0 === count($errTab)) {
    $destinataire = false;
    // ce user autorise t-il le contact
    $req = "SELECT civ_user, firstname_user, lastname_user, auth_contact_user, email_user
        FROM caf_user
        WHERE id_user = $id_user
        ";

    $handleSql = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $destinataire = $handle;
    }
    if (!$destinataire) {
        $errTab[] = 'Destinataire introuvable';
    }
    if (!isMail($destinataire['email_user'])) {
        $errTab[] = 'E-mail de destinataire invalide';
    }
}

// contact autorisé ? antipiratage
if (!isset($errTab) || 0 === count($errTab)) {
    $auth_contact_user = false;
    if ('none' == $destinataire['auth_contact_user']) {
        $errTab[] = 'Ce destinataire a désactivé le contact par e-mail.';
    }
    if ('users' == $destinataire['auth_contact_user'] && !user()) {
        $errTab[] = 'Vous devez être connecté pour contacter cette personne.';
    }
}

// ENVOI DU MAIL
if (!isset($errTab) || 0 === count($errTab)) {
    // content vars
    $subject = 'Contact sur le site du Club Alpin : '.$objet;
    $content_header = '';
    $content_main = '<h2>Bonjour '.html_utf8($destinataire['firstname_user']).' !<br /><br />Un visiteur du site vous a contacté sur <i>'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'</i></h2>'
        .'<b>Infos :</b><table>'
        .($nom ? '<tr><td><b>Nom : </b></td><td><a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'/user-full/'.$expediteur['id_user'].'.html">'.html_utf8($nom)."</a></td></tr>\n" : '')
        .($email ? '<tr><td><b>Email : </b></td><td><a href="mailto:'.html_utf8($email).'" title="">'.html_utf8($email).'</a> </td></tr>' : '')
        .($objet ? '<tr><td><b>Objet : </b></td><td>'.html_utf8($objet)." </td></tr>\n" : '')
        .'</table><br /><br />'
        .'<b>Message :</b><br />'.getUrlFriendlyString($message) // !!! cette variable doit déja etre sécurisée
        ;

    $content_footer = '';

    // PHPMAILER
    require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
    $mail = new CAFPHPMailer(); // defaults to using php "mail()"

    if ($email) {
        $mail->AddReplyTo($email);
    }
    $mail->AddAddress($destinataire['email_user'], $destinataire['firstname_user'].' '.$destinataire['lastname_user']);
    $mail->Subject = $subject;
    //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
    $mail->setMailBody($content_main);
    $mail->setMailHeader($content_header);
    $mail->setMailFooter($content_footer);
    // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

    if (!$mail->Send()) {
        $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter par téléphone pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
    }
}

// tout s'est bien passé, on vide les variables postées
if (!isset($errTab) || 0 === count($errTab)) {
    unset($_POST);
    $_POST['operation'] = 'user_contact';
}
