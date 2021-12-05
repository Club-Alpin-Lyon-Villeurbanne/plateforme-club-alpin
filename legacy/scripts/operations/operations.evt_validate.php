<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

$subject = $content_main = $authorDatas = null;

$id_evt = (int) ($_POST['id_evt']);
$status_evt = (int) ($_POST['status_evt']);

// checks
if (!$id_evt) {
    $errTab[] = "Erreur d'identifiant";
}
if (!allowed('evt_validate')) {
    $errTab[] = 'Vous ne semblez pas autorisé à effectuer cette opération';
}

// save
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "UPDATE caf_evt SET status_evt='$status_evt', status_who_evt=".getUser()->getIdUser()." WHERE caf_evt.id_evt =$id_evt";
    if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }

    // récupération des infos user et evt
    $req = "SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, id_evt, titre_evt, code_evt, tsp_evt FROM caf_user, caf_evt WHERE id_user=user_evt AND id_evt=$id_evt LIMIT 1";
    $result = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);
    $authorDatas = false;
    while ($row = $result->fetch_assoc()) {
        $authorDatas = $row;
    }
    if (!$authorDatas) {
        $errTab[] = 'User or evt not found';
    }
}

// envoi de mail à l'auteur pour - lui confirmer la création / OU / l'informer du refus
if ((!isset($errTab) || 0 === count($errTab)) && (1 == $status_evt || 2 == $status_evt)) {
    // content vars
    if (1 == $status_evt) {
        $subject = 'Votre sortie a été publiée sur le site';
        $content_main = "<h2>$subject</h2>
            <p>Félicitations, votre sortie &laquo;<i>".html_utf8($authorDatas['titre_evt']).'</i>&raquo;, prévue pour le '.date('d/m/Y', $authorDatas['tsp_evt']).' a été publiée sur le site du '.$p_sitename.' par les responsables. Pour y accéder, cliquez sur le lien ci-dessous :</p>
            <p>
                <a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html" title="">'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html</a>
            </p>';
    }
    if (2 == $status_evt) {
        $subject = 'Votre sortie a été refusée';
        $content_main = "<h2>$subject</h2>
            <p>Désolé, il semble que votre événement créé sur le site du ".$p_sitename.' ne soit pas validé par les responsables. Voici ci-dessous le message joint :</p>
            <p>&laquo;<i>'.html_utf8(stripslashes($_POST['msg'] ?: '...')).'</i>&raquo;</p>
            <p>Sortie concernée : &laquo;<i>'.html_utf8($authorDatas['titre_evt']).'</i>&raquo;, prévue pour le '.date('d/m/Y', $authorDatas['tsp_evt']).'</p>
            <p>
                Pour administrer vos sorties, rendez-vous sur votre profil :
                <a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'profil/sorties.html" title="">'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'profil/sorties.html</a>
            </p>
            ';
    }
    $content_header = '';
    $content_footer = '';

    // PHPMAILER
    require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
    $mail = new CAFPHPMailer(); // defaults to using php "mail()"

    // $mail->AddReplyTo();
    $mail->SetFrom($p_noreply, $p_sitename);
    $mail->AddAddress($authorDatas['email_user'], $authorDatas['firstname_user'].' '.$authorDatas['lastname_user']);
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

// Si l'événement est validé, on avertit les sbires, joints par l'auteur, qu'ils sont inscrits d'office à l'événement
if ((!isset($errTab) || 0 === count($errTab)) && 1 == $status_evt) {
    // liste des personnes inscrites (sauf l'auteur : un e-mail suffit)
    $handle['joins'] = [];
    $req = "SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user
            , role_evt_join
        FROM caf_evt_join, caf_user
        WHERE evt_evt_join =$id_evt
        AND status_evt_join = 1
        AND user_evt_join = id_user
        AND id_user != ".$authorDatas['id_user'].'
        LIMIT 300';

    $result = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);

    while ($row = $result->fetch_assoc()) {
        // construction du mail
        $subject = 'Vous êtes inscrit à une sortie qui vient d\'être publiée';
        $content_main = "<h2>$subject</h2>
            <p>Bonjour ".$row['firstname_user'].",</p>
            <p>
                Une nouvelle sortie vient d'être publiée sur le site.
                <a href=\"".$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'voir-profil/'.(int) ($authorDatas['id_user']).'.html">'.$authorDatas['nickname_user'].'</a>
                vous a pré-inscrit pour cette sortie en tant que <b>'.$row['role_evt_join'].'</b>.
            </p>
            <p>
                Pour voir la fiche de cette sortie, cliquez ci-dessous :<br />
                <a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html" title="">'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html</a>
            </p>
            ';
        $content_header = '';
        $content_footer = '';

        // PHPMAILER
        require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
        $mail = new CAFPHPMailer(); // defaults to using php "mail()"
        //$mail->CharSet = 'UTF-8';
        //$mail->IsHTML(true);
        $mail->SetFrom($p_noreply, $p_sitename);
        // $mail->AddReplyTo();
        $mail->AddAddress($row['email_user'], $row['firstname_user'].' '.$row['lastname_user']);
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
}
