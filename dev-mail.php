<?php

    //_________________________________________________ DEFINITION DES DOSSIERS
    define('DS', \DIRECTORY_SEPARATOR);
    define('ROOT', __DIR__.DS);				// Racine
    define('APP', ROOT.'app'.DS);						// Applicatif

    require_once APP.'mailer'.DS.'class.phpmailer.caf.php';

    $mail = new CAFPHPMailer(); // defaults to using php "mail()"
    $mail->Subject = 'ceci est un test';
    $mail->AddAddress('e.henke@herewecom.fr');
    $mail->ClearReplyTos();

    $mail->setMailBody('<h2>Bonjour !</h2><p><b>Message :</b><br />gael<br />&nbsp;</p>'.strftime('%H:%M:%S'));

    $mail->AddReplyTo('gmondon@free.fr');

    if (!$mail->Send()) {
        echo $mail->ErrorInfo;
    } else {
        echo 'ok:';
    }

    echo strftime('%H:%M:%S');
