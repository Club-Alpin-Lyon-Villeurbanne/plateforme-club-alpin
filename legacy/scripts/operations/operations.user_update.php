<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_user = $email_user_mailchange = null;

// check user online
if (!user()) {
    $errTab[] = "Vous avez été déconnecté. L'opération n'a pas été effectuée.";
}

// mise à jour infos texte
if (!isset($errTab) || 0 === count($errTab)) {
    $id_user = getUser()->getId();
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
    if (!isMail($email_user_mailchange) && '' !== $email_user_mailchange) {
        $errTab[] = 'Vous avez demandé à remplacer votre adresse e-mail, mais elle semble invalide.';
    }

    // 04/09/2013 - gmn - desactivation car import FFCAM => E.HENKE : on doit malgré tout pouvoir enregistrer les infos personnelles de contact
    if (!isset($errTab) || 0 === count($errTab)) {
        $auth_contact_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($auth_contact_user);
        $req = "UPDATE `caf_user`
            SET
            `auth_contact_user` = '$auth_contact_user'
            WHERE `id_user` =$id_user LIMIT 1 ;";

        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
    }
}

// mise à jour de la photo si transmise
if ((!isset($errTab) || 0 === count($errTab)) && $_FILES['photo']['size'] > 0) {
    if ($_FILES['photo']['error'] > 0) {
        $errTab[] = "Erreur dans l'image : ".$_FILES['photo']['error'];
    } else {
        // déplacement du fichier dans le dossier transit
        $uploaddir = __DIR__.'/../../../public/ftp/transit/profil/';
        LegacyContainer::get('legacy_fs')->mkdir($uploaddir);
        $i = 1;
        while (file_exists($uploaddir.$i.'-profil.jpg')) {
            ++$i;
        }
        $filename = $i.'-profil.jpg';
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploaddir.$filename)) {
            $rep_Dst = __DIR__.'/../../../public/ftp/user/'.$id_user.'/';

            $profilePic = $rep_Dst.'profil.jpg';
            $uploadedFile = $uploaddir.$filename;

            LegacyContainer::get('legacy_fs')->mkdir($rep_Dst);

            if (!ImageManipulator::resizeImage(1000, 1000, $uploadedFile, $profilePic)) {
                $errTab[] = 'Impossible de redimensionner la grande image';
            }

            $profilePicMin = $rep_Dst.'min-profil.jpg';
            $profilePicPic = $rep_Dst.'pic-profil.jpg';

            if (!ImageManipulator::resizeImage(150, 150, $uploadedFile, $profilePicMin)) {
                $errTab[] = 'Impossible de redimensionner la miniature';
            }

            if (!ImageManipulator::cropImage(55, 55, $uploadedFile, $profilePicPic)) {
                $errTab[] = 'Impossible de croper l\'image (picto)';
            }

            if (file_exists($uploaddir.$filename)) {
                unlink($uploaddir.$filename);
            }
        } else {
            $errTab[] = 'Erreur lors du déplacement du fichier';
        }
    }
}

// si mise à jour e-mail user
if ('' !== $email_user_mailchange) {
    $email_user_mailchange = LegacyContainer::get('legacy_mysqli_handler')->escapeString($email_user_mailchange);
    $token = $id_user_mailchange = null;

    // VERIFICATIONS
    // compte des entrées existantes avec cet e-mail
    $req = "SELECT COUNT(id_user) FROM caf_user WHERE email_user LIKE '$email_user_mailchange' AND id_user != $id_user ";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if (getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM)) > 0) {
        $errTab[] = "Votre demande de modification d'e-mail est refusée : Un compte existe déjà avec cette adresse e-mail.";
    }

    // ENTRÉE DE LA DEMANDE DANS LA BD
    if (!isset($errTab) || 0 === count($errTab)) {
        $token = bin2hex(random_bytes(16));
        $req = "INSERT INTO `caf_user_mailchange` (`user_user_mailchange` , `token_user_mailchange` , `email_user_mailchange` )
                                                    VALUES ('$id_user',				'$token', 				'$email_user_mailchange');";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        } else {
            $id_user_mailchange = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
    }

    // ENVOI DU MAIL
    if (!isset($errTab) || 0 === count($errTab)) {
        // check-in vars : string à retourner lors de la confirmation= md5 de la concaténation id-email
        $url = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'email-change/'.$token.'-'.$id_user_mailchange.'.html';

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

        // PHPMAILER
        require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
        $mail = new CAFPHPMailer(); // defaults to using php "mail()"

        $mail->AddAddress($email_user_mailchange, getUser()->getNickname());
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
