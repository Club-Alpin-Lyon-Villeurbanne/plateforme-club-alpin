<?php

require_once __DIR__.'/app/mailer/class.phpmailer.caf.php';

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
