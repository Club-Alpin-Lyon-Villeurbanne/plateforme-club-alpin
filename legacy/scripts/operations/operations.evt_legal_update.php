<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

$id_evt = (int) ($_POST['id_evt']);
$status_legal_evt = (int) ($_POST['status_legal_evt']);

// checks
if (!$id_evt) {
    $errTab[] = "Erreur d'identifiant";
}
if (!allowed('evt_legal_accept')) {
    $errTab[] = 'Vous ne semblez pas autorisé à effectuer cette opération';
}

$authorDatas = $subject = $content_main = null;

// save
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "UPDATE caf_evt SET status_legal_evt='$status_legal_evt', status_legal_who_evt=".getUser()->getIdUser()." WHERE caf_evt.id_evt =$id_evt";
    if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }

    // récupération des infos user et evt
    $req = "SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, id_evt, titre_evt, code_evt, tsp_evt FROM caf_user, caf_evt WHERE id_user=user_evt AND id_evt=$id_evt LIMIT 1";
    $result = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $authorDatas = $row;
    }
    if (!$authorDatas) {
        $errTab[] = 'User or evt not found';
    }
}

// envoi de mail à l'auteur pour - lui confirmer la validation / OU / l'informer du refus
if ((!isset($errTab) || 0 === count($errTab)) && (1 == $status_legal_evt || 2 == $status_legal_evt)) {
    // content vars
    if (1 == $status_legal_evt) {
        $subject = 'Votre sortie a été validée par le président';
        $content_main = "<h2>$subject</h2>
            <p>Félicitations, votre sortie &laquo;<i>".html_utf8($authorDatas['titre_evt']).'</i>&raquo;, prévue pour le '.date('d/m/Y', $authorDatas['tsp_evt']).' a été validée. Pour y accéder, cliquez sur le lien ci-dessous :</p>
            <p>
                <a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html" title="">'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html</a>
            </p>';
    }
    if (2 == $status_legal_evt) {
        $subject = 'Votre sortie a été refusée à la validation';
        $content_main = "<h2>$subject</h2>
            <p>Désolé, il semble que votre sortie créée sur le site du ".$p_sitename.' ne soit pas validée par le CAF</p>
            <p>Sortie concernée : &laquo;<i>'.html_utf8($authorDatas['titre_evt']).'</i>&raquo;, prévue pour le '.date('d/m/Y', $authorDatas['tsp_evt']).'</p>
            <p>
                Voir la page :<br />
                <a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html" title="">'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html</a>
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

    // débug local
    if ('127.0.0.1' == $_SERVER['HTTP_HOST']) {
        $mail->IsMail();
    }

    if (!$mail->Send()) {
        $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
    }
}
