<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_evt = (int) ($_POST['id_evt']);
$id_user = getUser()->getId();

$evtDate = $evtTarif = $encEmail = $encName = null;

if (!$id_user || !$id_evt) {
    $errTab[] = 'Erreur de données';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // Informations sur l'événement
    $req = "SELECT id_evt, titre_evt, tsp_evt, tarif_evt, code_evt FROM caf_evt WHERE id_evt=$id_evt LIMIT 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $row = $result->fetch_row();
    if ($row) {
        $evtId = html_utf8($row[0]);
        $evtCode = html_utf8($row[4]);
        $evtName = html_utf8($row[1]);
        $evtDate = html_utf8(date('d-m-Y', $row[2]));
        $evtTarif = html_utf8($row[3]);
        $evtUrl = html_utf8(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.stripslashes($evtCode).'-'.$evtId.'.html');
    }

    // Informations sur l'encadrant
    $req = "SELECT B.firstname_user, B.lastname_user, B.email_user
            FROM caf_evt_join AS A
            LEFT JOIN caf_user AS B
                ON A.user_evt_join = B.id_user
            WHERE A.evt_evt_join=$id_evt
            AND (A.role_evt_join LIKE 'encadrant')
            LIMIT 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $row = $result->fetch_row();
    if ($row) {
        $encName = html_utf8($row[0].' '.$row[1]);
        $encEmail = html_utf8($row[2]);
    }

    // récupération du statut de l'inscription : si elle est valide, l'orga recoit un e-mail
    $req = "SELECT status_evt_join, is_cb FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user ORDER BY tsp_evt_join DESC LIMIT 1 ";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $status_evt_join = 0;
    $is_cb = 0;
    while ($row = $result->fetch_assoc()) {
        $status_evt_join = $row['status_evt_join'];
        $is_cb = $row['is_cb'];
    }

    if (1 == $status_evt_join || 0 == $status_evt_join) {
        // envoi du mail à l'orga
        // recup de son email & nom
        $toMail = '';
        $toName = '';
        $req = "SELECT email_user, firstname_user, lastname_user FROM caf_user, caf_evt WHERE id_evt=$id_evt AND user_evt=id_user LIMIT 1";
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($row = $result->fetch_assoc()) {
            $toMail = $row['email_user'];
            $toName = $row['firstname_user'].' '.$row['lastname_user'];
        }
        if (!isMail($toMail)) {
            $errTab[] = 'Les coordonnées du contact sont erronées';
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            // si pas de pb, suppression de l'inscription
            $req = "DELETE FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user";
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }

            // phpmailer
            require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';

            // vars
            $tmpUserName = (getUser()->getFirstname().' '.getUser()->getLastname());
            $evtName = html_utf8($_POST['titre_evt']);
            $evtUrl = html_utf8(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.stripslashes($_POST['code_evt']).'-'.$_POST['id_evt'].'.html');

            // contenu
            $subject = 'Désinscription de '.$tmpUserName;
            $content_main = "<h2>$subject</h2>
                <p>
                    ".html_utf8($tmpUserName)."
                    était inscrit à la sortie <a href='$evtUrl'>$evtName</a>. <b>Il vient de supprimer son inscription.</b>
                </p>
                <p>
                    Plus d'infos :
                </p>
                <ul>
                    <li><b>Pseudo : </b> ".html_utf8(getUser()->getNickname())."</li>
                    <li><b>Email : </b> <a href='mailto:".html_utf8(getUser()->getEmail())."'>".html_utf8(getUser()->getEmail()).'</a></li>
                    <li><b>Tel : </b> '.html_utf8(getUser()->getTel()).'</li>
                    <li><b>Tel2 : </b> '.html_utf8(getUser()->getTel2()).'</li>
                </ul>
                ';
            $content_header = '';
            $content_footer = '';

            $mail = new CAFPHPMailer(); // defaults to using php "mail()"

            $mail->AddReplyTo(getUser()->getEmail());
            $mail->Subject = $subject;
            //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
            $mail->setMailBody($content_main);
            $mail->setMailHeader($content_header);
            $mail->setMailFooter($content_footer);
            $mail->AddAddress($toMail, $toName);
            // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

            if (!$mail->Send()) {
                $errTab[] = "Échec à l'envoi du mail à ".$toMail.". Plus d'infos : ".($mail->ErrorInfo);
            }

            // paiement en ligne
            // envoi d'un email pour indiquer la désinscription d'un participant avec paiement en ligne
            if (1 == $is_cb) {
                $toMail = 'comptabilite@clubalpinlyon.fr';
                $toName = 'Trésorier';
                $toNameFull = getUser()->getFirstname().' '.getUser()->getLastname();
                $toCafNum = getUser()->getCafnum();

                $subject = 'Désinscription avec paiement en ligne';
                $content_main = "<h2>$subject</h2>
                    <p>
                        Bonjour,<br />
                        Un participant ayant payé en ligne vient de se <span color='green'>désinscrire</span>
                         de la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
                    </p>
                    <p>
                        Merci de prendre contact avec l'encadrant.
                        <br />
                        La gestion du paiement (modification/remboursement) se passe sur https://paiement.systempay.fr/vads-merchant/.
                    </p>
                    <p>
                        Informations supplémentaires :<br />
                        <ul>
                            <li>Transaction : $evtName du $evtDate - $toNameFull</li>
                            <li>Sortie : $evtName du $evtDate</li>
                            <li>Montant : $evtTarif</li>
                            <li>Adhérent : $toNameFull / $toCafNum</li>
                            <li>Endadrant : $encName / $encEmail</li>
                        </ul>
                        <br />
                        Bonne journée.
                    </p>
                ";

                $content_header = '';
                $content_footer = '';

                $mail = new CAFPHPMailer(); // defaults to using php "mail()"

                $mail->Subject = $subject;
                $mail->setMailBody($content_main);
                $mail->setMailHeader($content_header);
                $mail->setMailFooter($content_footer);
                $mail->AddAddress($toMail, $toName);

                // débug local
                if ('127.0.0.1' == $_SERVER['HTTP_HOST']) {
                    $mail->IsMail();
                }

                if (!$mail->Send()) {
                    $errTabMail[] = "Échec à l'envoi du mail à ".html_utf8($toMail).". Plus d'infos : ".($mail->ErrorInfo);
                }
            }
            // fin paiement en ligne
        }
    }
}
