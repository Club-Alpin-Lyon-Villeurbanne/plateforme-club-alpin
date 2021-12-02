<?php

$email_user = trim(stripslashes($_POST['email_user']));
$mdp_user = trim(stripslashes($_POST['mdp_user']));
// **********
// verification pswd valide
if (strlen($mdp_user) < 5 || strlen($mdp_user) > 12) {
    $errTab[] = $fieldsErrTab['mdp_user'] = 'Le mot de passe doit faire entre 5 et 12 caractères';
}
if (strpos($mdp_user, ' ')) {
    $errTab[] = $fieldsErrTab['mdp_user'] = "Le mot de passe ne doit pas comporter d'espace";
}

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

// **********
// verification de l'existence ou non du compte
$email_user = $mysqli->real_escape_string(htmlspecialchars($email_user, \ENT_NOQUOTES, 'UTF-8'));

$req = 'SELECT id_user FROM '.$pbd."user WHERE email_user LIKE '$email_user' AND `valid_user`=1 LIMIT 1";
$handleSql = $mysqli->query($req);
$found = false;
$id_user_mdpchange = $token = $nickname_user = null;

// si le compte existe, entrée de la demande dans la BD
while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    $found = true;
    $token = md5(time() + rand(100, 999));
    if ($use_md5_salt) {
        $mdp_user = md5($mdp_user.$md5_salt);
    } else {
        $mdp_user = md5($mdp_user);
    }
    $req = 'INSERT INTO `'.$pbd."user_mdpchange`
                (`user_user_mdpchange` , `token_user_mdpchange` , `pwd_user_mdpchange` )
                VALUES ('".$handle['id_user']."', '$token', '$mdp_user');";
    if (!$mysqli->query($req)) {
        $errTab[] = 'Erreur SQL : '.$mysqli->error;
        error_log($mysqli->error);
    } else {
        $id_user_mdpchange = $mysqli->insert_id;
    }
}
// compte introuvable
if (!$found) {
    $errTab[] = $fieldsErrTab['email_user'] = "Aucun compte n'a été trouvé avec cette adresse e-mail";
}

$mysqli->close();

// ENVOI DU MAIL
if (!isset($errTab) || 0 === count($errTab)) {
    // check-in vars : string à retourner lors de la confirmation= md5 de la concaténation id-email
    $url = $p_racine.'mot-de-passe-perdu/'.$token.'-'.$id_user_mdpchange.'.html';

    // content vars
    $subject = 'Votre mot de passe du '.$p_sitename.' !';
    $content_header = '';
    $content_main = '
        <h1>'.$subject.'</h1>
        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous pour confirmer
        et vous n\'aurez plus qu\'à vous connecter avec le mot de passe que vous avez demandé.
        Attention ce lien ne fonctionne que pendant une heure.</p>
        <p><a class="bigLink" href="'.$url.'" title="">'.$url.'</a></p>
        <p>Si vous n\'avez pas demandé à recevoir ce mail, il suffit de l\'ignorer.</p>
        ';
    $content_footer = '';

    // PHPMAILER
    require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
    $mail = new CAFPHPMailer(); // defaults to using php "mail()"

    $mail->SetFrom($p_noreply, $p_sitename);
    $mail->AddAddress($email_user, $nickname_user);
    $mail->Subject = $subject;
    //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
    $mail->setMailBody($content_main);
    $mail->setMailHeader($content_header);
    $mail->setMailFooter($content_footer);
    // $mail->setMailBody('TEST');
    // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

    if (!$mail->Send()) {
        $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter par téléphone pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
    } else {
        $successmsg = "Un e-mail vient de vous être envoyé à $email_user. \n Cliquez sur le lien contenu dans celui-ci pour confirmer la mise à jour du mot de passe.";
    }
}
