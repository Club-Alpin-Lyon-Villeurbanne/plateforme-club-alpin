<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

$lastname_user = trim(stripslashes($_POST['lastname_user']));
$cafnum_user = preg_replace('/\s+/', '', stripslashes($_POST['cafnum_user']));
$email_user = strtolower(trim(stripslashes($_POST['email_user'])));
$mdp_user = trim(stripslashes($_POST['mdp_user']));

if (strlen($lastname_user) < 2) {
    $errTab[] = 'Merci de renseigner un nom de famille valide';
}
if (strlen($cafnum_user) != $limite_longeur_numero_adherent) {
    $errTab[] = "Merci de renseigner un numéro d'adhérent CAF valide ($limite_longeur_numero_adherent chiffres)";
}
if (!isMail($email_user)) {
    $errTab[] = 'Merci de renseigner une adresse e-mail valide';
}
if (strlen($mdp_user) < 8 || strlen($mdp_user) > 40) {
    $errTab[] = 'Le mot de passe doit faire de 8 à 40 caractères';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
    // formatage sécurité
    $lastname_user = $mysqli->real_escape_string($lastname_user);
    $cafnum_user = $mysqli->real_escape_string($cafnum_user);
    $email_user = $mysqli->real_escape_string($email_user);
    $mdp_user = $kernel->getContainer()->get('legacy_hasher_factory')->getPasswordHasher('login_form')->hash($mdp_user);

    // Si ce compte a été désactivé
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "SELECT COUNT(id_user)
            FROM caf_user
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
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "SELECT COUNT(id_user)
            FROM caf_user
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
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "SELECT COUNT(id_user)
            FROM caf_user
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
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "SELECT COUNT(id_user) FROM caf_user WHERE cafnum_user = '$cafnum_user' LIMIT 1";
        $result = $mysqli->query($req);
        $row = $result->fetch_row();
        if (!$row[0]) {
            $errTab[] = "Désolé, nous ne trouvons pas ce numéro d'adhérent dans notre base de donnée. Si vous venez de vous (ré)inscrire au CAF, nous vons invitons à réessayer ultérieurement.";
        }
    }

    // vérification de l'obsolescence du compte
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "SELECT COUNT(id_user) FROM caf_user WHERE cafnum_user = '$cafnum_user' AND doit_renouveler_user =1 LIMIT 1";
        $result = $mysqli->query($req);
        $row = $result->fetch_row();
        if ($row[0]) {
            $errTab[] = 'La licence pour ce compte semble être expirée. Si vous venez de renouveler votre licence nous vous invitons à réessayer ultérieurement.';
        }
    }

    $id_user = $nickname_user = $cookietoken_user = $firstname_user = null;

    // le nom colle ?
    if (!isset($errTab) || 0 === count($errTab)) {
        $id_user = false;
        $req = "SELECT id_user
            FROM caf_user
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
    if (!isset($errTab) || 0 === count($errTab)) {
        $nickname_user = false;
        // id_user défini juste avant
        $req = "SELECT lastname_user, firstname_user
            FROM caf_user
            WHERE id_user = $id_user
            LIMIT 1";
        $result = $mysqli->query($req);
        while ($row = $result->fetch_assoc()) {
            $firstname_user = ucfirst(mb_strtolower($row['firstname_user'], 'UTF-8'));
            $nickname_user = str_replace([' ', '-', '\''], '', $firstname_user.substr(strtoupper($row['lastname_user']), 0, 1));
        }
        $nickname_user = $mysqli->real_escape_string($nickname_user);

        if (!$nickname_user) {
            $errTab[] = 'Impossible de générer le pseudo. Merci de nous contacter.';
        }
    }

    // tt ok ? activation
    // intégration des valeurs données et du token nécessaire à la confirmation par email
    if (!isset($errTab) || 0 === count($errTab)) {
        $cookietoken_user = bin2hex(random_bytes(16));
        $req = "UPDATE caf_user SET email_user = '$email_user',
            mdp_user = '$mdp_user',
            nickname_user = '$nickname_user',
            created_user = ".time().",
            cookietoken_user = '$cookietoken_user'
            WHERE id_user =$id_user LIMIT 1 ;";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur de sauvegarde';
        }
    }

    // envoi de l'e-mail
    if (!isset($errTab) || 0 === count($errTab)) {
        // check-in vars : string à retourner lors de la confirmation= md5 de la concaténation id-email
        $url = $kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'user-confirm/'.$cookietoken_user.'-'.$id_user.'.html';

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

        // PHPMAILER
        require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
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
}
