<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

if (!user()) {
    $errTab[] = 'Seuls les adhérents connectés peuvent commenter pour le moment';
}
if ('unlocked' != $_POST['unlock1']) {
    $errTab[] = "L'antispam n'a pas autorisé l'envoi. Merci de cliquer sur le bouton &laquo;OK&raquo; pour envoyer le message.";
}

// recep' vars
$cont_comment = trim(stripslashes($_POST['cont_comment']));
switch ($p1) {
    case 'article':
        $parent_type_comment = $p1;
        break;
    default:
        $parent_type_comment = false;
}
$parent_comment = (int) ($_POST['parent_comment']);

// checks
if (strlen($cont_comment) < 10) {
    $errTab[] = 'Par soucis de pertinence, les commentaires doivent être supérieurs à 10 caractères.';
}
if (!$parent_type_comment) {
    $errTab[] = 'Parent type non défini.';
}
if (!$parent_comment) {
    $errTab[] = 'Parent non défini.';
}

$comment_article = null;

// checks SQL
if (!isset($errTab) || 0 === count($errTab)) {
    // article publié et commentable ?
    $req = 'SELECT a.id_article, a.user_article, u.email_user, a.titre_article, a.code_article
            FROM caf_'.$parent_type_comment.' a, caf_user u
            WHERE u.id_user=a.user_article
            AND a.id_'.$parent_type_comment." = $parent_comment
            AND a.status_".$parent_type_comment.' = 1
            LIMIT 1';
    $result = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);
    $row = $result->fetch_row();
    if (!$row[0]) {
        $errTab[] = "L'élément visé ne semble pas publié.";
    } else {
        $comment_article = $row;
    }
}

// insert SQL
if (!isset($errTab) || 0 === count($errTab)) {
    // formatage
    $cont_comment_mysql = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($cont_comment);

    // article publié et commentable ?
    $req = "INSERT INTO caf_comment(id_comment, status_comment, tsp_comment, user_comment, name_comment, email_comment, cont_comment, parent_type_comment, parent_comment)
                            VALUES (NULL ,  '1', 			 '".time()."',  '".getUser()->getIdUser()."',  '',  '',  '$cont_comment_mysql',  '$parent_type_comment',  '$parent_comment');";
    if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }

    // PHPMAILER - envoi mail vers auteur
    if ('' !== $comment_article[2]) {
        $content_main = '<h1>Bonjour !<h1><p>Votre article <a href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'article/'.$comment_article[4].'-'.$comment_article[0].'.html#comments" title="">'.$comment_article[3].'</a> a été commenté avec le texte suivant :</p><p><i>'.$cont_comment.'</i></p><br /><br /><p>PS : ceci est un mail automatique.</p>';

        require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
        $mail = new CAFPHPMailer(); // defaults to using php "mail()"
        //$mail->CharSet = 'UTF-8';
        //$mail->IsHTML(true);
        $mail->AddReplyTo(getUser()->getEmailUser() ?: $p_noreply);
        $mail->SetFrom($p_noreply, $p_sitename);
        $mail->AddAddress($comment_article[2]);
        $mail->Subject = $p_sitename." - J'ai ajouté un commentaire à votre article !";
        //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
        $mail->setMailBody($content_main);
        $mail->setMailHeader(isset($content_header) ? $content_header : '');
        $mail->setMailFooter(isset($content_footer) ? $content_footer : '');

        // débug local
        if ('127.0.0.1' == $_SERVER['HTTP_HOST']) {
            $mail->IsMail();
        }

        if (!$mail->Send()) {
            $errTab[] = "Échec à l'envoi du mail. Merci de nous contacter par téléphone pour nous faire part de cette erreur... Plus d'infos : ".($mail->ErrorInfo);
        }
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    $_POST['cont_comment'] = '';
}
