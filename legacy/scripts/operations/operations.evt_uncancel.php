<?php

global $kernel;

$id_evt = (int) (substr(strrchr($p2, '-'), 1));

// checks
if (!$id_evt) {
    $errTab[] = 'ID invalide';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // recuperation de la sortie demandée
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
    $req = "SELECT id_evt, code_evt, status_evt, titre_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, tsp_evt, code_commission,
        id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user
        FROM caf_evt, caf_user, caf_commission
        WHERE id_evt=$id_evt
        AND id_user = user_evt
        AND commission_evt=id_commission
        LIMIT 1";
    $handleSql = $mysqli->query($req);

    if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // on a le droit d'annuler ?
        if (!(allowed('evt_cancel', 'commission:'.$handle['code_commission']) || allowed('evt_cancel_any'))) {
            $errTab[] = 'Accès non autorisé';
        } else {
            // mise à jour
            if (!isset($errTab) || 0 === count($errTab)) {
                // si cette sortie fait partie d'un cycle et si la premiere du cycle est annulee on dissocie la sortie actuelle
                $cycle_parent_evt = $handle['cycle_parent_evt'];
                if ($handle['cycle_parent_evt'] > 0) {
                    $req = "SELECT id_evt FROM caf_evt WHERE id_evt=$id_evt AND cancelled_evt='0'";
                    if (!$handleSql2 = $mysqli->query($req)) {
                        $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                            'error' => $mysqli->error,
                            'file' => __FILE__,
                            'line' => __LINE__,
                            'sql' => $req,
                        ]);
                        $errTab[] = 'Erreur SQL SELECT COUNT';
                    } elseif ($handleSql2->num_rows() > 0) {
                        $cycle_parent_evt = 0;
                    }
                }

                $req = "UPDATE caf_evt SET cancelled_evt='0', cancelled_who_evt='0', cancelled_when_evt='0', status_evt='0',cycle_parent_evt=$cycle_parent_evt WHERE caf_evt.id_evt =$id_evt";
                if (!$mysqli->query($req)) {
                    $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                        'error' => $mysqli->error,
                        'file' => __FILE__,
                        'line' => __LINE__,
                        'sql' => $req,
                    ]);
                    $errTab[] = 'Erreur SQL';
                }

                // envoi mail encadrant
                $subject = 'Votre sortie a été réactivée sur le site';
                $content_main = "<h2>$subject</h2>
                    <p>Félicitations, votre sortie &laquo;<i>".html_utf8($handle['titre_evt']).'</i>&raquo;, prévue pour le '.date('d/m/Y', $handle['tsp_evt']).' a été réactivée sur le site du '.$p_sitename.".<br /><br /><b>Pensez à ajouter des (co)encadrants.</b><br /><br /> Pour y accéder, cliquez sur le lien ci-dessous :</p>
                    <p>
                        <a href=\"$p_racine".'sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].".html\" title=\"\">$p_racine".'sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].'.html</a>
                    </p>';

                if ($handle['cycle_master_evt']) {
                    $content_main .= '<b>Cette sortie est un début de cycle, pensez à réactiver les sorties suivantes !</b>';
                }

                $content_header = '';
                $content_footer = '';

                // PHPMAILER
                require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
                $mail = new CAFPHPMailer(); // defaults to using php "mail()"

                // $mail->AddReplyTo();
                $mail->SetFrom($p_noreply, $p_sitename);
                $mail->AddAddress($handle['email_user'], $handle['firstname_user'].' '.$handle['lastname_user']);
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

            // redirection vers la page de la sortie
            if (!isset($errTab) || 0 === count($errTab)) {
                header('Location:'.$p_racine.'sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].'.html');
                exit;
            }
        }
    }
}
