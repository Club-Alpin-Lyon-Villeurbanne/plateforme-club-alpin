<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

$id_evt = (int) (substr(strrchr($p2, '-'), 1));
$msg = trim(stripslashes($_POST['msg']));
$nomadMsg = []; // message spécial par raport aux nomades

// checks
if (!strlen($msg)) {
    $errTab[] = 'Veuillez entrer un message';
}
if (!$id_evt) {
    $errTab[] = 'ID invalide';
}

// recuperation de la sortie demandée
$req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
            , nickname_user
            , title_commission, code_commission
    FROM caf_evt, caf_user, caf_commission
    WHERE id_evt=$id_evt
    AND id_user = user_evt
    AND commission_evt=id_commission
    LIMIT 1";
$handleSql = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);

if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // on a le droit d'annuler ?
    if (!allowed('evt_cancel', 'commission:'.$handle['code_commission'])) {
        $errTab[] = 'Accès non autorisé';
    }

    // Mise à jour : annulation
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "UPDATE caf_evt SET cancelled_evt='1', cancelled_who_evt='".getUser()->getIdUser()."', cancelled_when_evt='".time()."'  WHERE caf_evt.id_evt =$id_evt";
        // annulation de toutes les sorties du cycle
        if (true || $_POST['del_cycle_master_evt']) {
            $req .= " OR caf_evt.cycle_parent_evt=$id_evt";
        }

        if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
    }

    // message aux participants si la sortie est annulée alors qu'elle est publiée
    if ((!isset($errTab) || 0 === count($errTab)) && 1 == $handle['status_evt']) {
        // phpmailer
        require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';

        // contenu commun à chaque envoi
        $subject = 'Sortie du '.date('d/m/Y', $handle['tsp_evt']).' annulée !';
        $content_main = "<h2>$subject</h2>
            <p>
                La sortie ".$handle['title_commission'].' du '.date('d/m/Y', $handle['tsp_evt']).',
                &laquo;<i> '.html_utf8($handle['titre_evt'])." </i>&raquo;
                vient d'être annulée par <a href=\"".$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'voir-profil/'.getUser()->getIdUser().'.html">'.getUser()->getNicknameUser().'</a>.
                Voici le message joint :
            </p>
            <p>&laquo;<i> '.nl2br(html_utf8($msg))." </i>&raquo;</p>
            <p><a href='".$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.html_utf8($handle['code_evt']).'-'.(int) ($handle['id_evt']).".html' title=''>&lt; Voir la page dédiée</a></p>
            ";
        $content_header = '';
        $content_footer = '';

        // participants:
        // si la sortie est enfant d'un cycle, on cherche les participants à la sortie parente
        if ($handle['cycle_parent_evt']) {
            $id_evt_forjoins = (int) ($handle['cycle_parent_evt']);
        } else {
            $id_evt_forjoins = (int) ($handle['id_evt']);
        }

        $handle['joins'] = [];
        $req = "SELECT id_evt_join, id_user, email_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user
            FROM caf_evt_join, caf_user
            WHERE evt_evt_join = $id_evt_forjoins
            AND user_evt_join = id_user
            LIMIT 300";
        $handleSql2 = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);

        // desinscription des participants de la sortie
        if (!isset($errTab) || 0 === count($errTab)) {
            $req = "DELETE FROM caf_evt_join WHERE role_evt_join NOT IN ('encadrant', 'coencadrant') AND (caf_evt_join.evt_evt_join = $id_evt";

            // desinscription de toutes les sorties du cycle si annulation du cycle complet, normalement y'en a pas...
            if (true || $_POST['del_cycle_master_evt']) {
                $req .= " OR caf_evt_join.evt_evt_join IN (SELECT DISTINCT id_evt FROM caf_evt WHERE cycle_parent_evt = $id_evt)";
            }
            $req .= ')';
            if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
        }

        // PHPMAILER
        // ENVOI DU MAIL A CHACUN
        $mail = new CAFPHPMailer(); // defaults to using php "mail()"
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
    // redirection vers la page de la sortie avec le message "annulé"
    if (!isset($errTab) || 0 === count($errTab)) {
        // sans message d'avertissement nomades
        if (!count($nomadMsg)) {
            header('Location: /sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].'.html');
        // echo 'nop';
        } else {
            header('Location: /sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].'.html?lbxMsg=nomadMsg&nomadMsg='.(implode('****', $nomadMsg)));
        }
    }
}
