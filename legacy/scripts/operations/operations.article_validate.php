<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_article = (int) ($_POST['id_article']);
$status_article = (int) ($_POST['status_article']);

// checks
if (!$id_article) {
    $errTab[] = "Erreur d'identifiant";
}
if (!allowed('article_validate')) {
    $errTab[] = 'Vous ne semblez pas autorisé à effectuer cette opération';
}

$authorDatas = null;

// save
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "UPDATE caf_article SET status_article='$status_article', status_who_article=".getUser()->getIdUser()." WHERE caf_article.id_article =$id_article";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }
    $req = 'UPDATE caf_article SET tsp_validate_article='.time()." WHERE caf_article.id_article=$id_article AND (tsp_validate_article=0 OR tsp_validate_article IS NULL)"; // premiere validation
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }

    // récupération des infos user et article
    $req = "SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, id_article, titre_article, code_article, tsp_crea_article, tsp_article FROM caf_user, caf_article WHERE id_user=user_article AND id_article=$id_article LIMIT 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $authorDatas = $row;
    }
    if (!$authorDatas) {
        $errTab[] = 'User or article not found';
    }

    $sql = 'SELECT (COUNT(id_article) - 5) as total FROM caf_article WHERE status_article > 0 AND une_article > 0';
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $limit = max(0, $row['total']);

        $sql = 'UPDATE caf_article SET une_article = 0 WHERE status_article = 1 AND une_article = 1 ORDER BY tsp_article ASC LIMIT '.$limit;
        LegacyContainer::get('legacy_mysqli_handler')->query($sql);
    }
}

// envoi de mail à l'auteur pour - lui confirmer la création / OU / l'informer du refus
if ((!isset($errTab) || 0 === count($errTab)) && (1 == $status_article || 2 == $status_article)) {
    // content vars
    $subject = $content_main = null;

    if (1 == $status_article) {
        $subject = 'Votre article a été publié sur le site';
        $url = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'article/'.$authorDatas['code_article'].'-'.$authorDatas['id_article'].'.html';
        $content_main = "<h2>$subject</h2>
            <p>Félicitations, votre article &laquo;<i>".html_utf8($authorDatas['titre_article']).'</i>&raquo;, créé le '.date('d/m/Y', $authorDatas['tsp_crea_article']).' a été publié sur le site du '.$p_sitename.' par les responsables. Pour y accéder, cliquez sur le lien ci-dessous :</p>
            <p>
                <a href="'.$url.'" title="">'.$url.'</a>
            </p>';
    }
    if (2 == $status_article) {
        $subject = 'Votre article a été refusé';
        $content_main = "<h2>$subject</h2>
            <p>Désolé, il semble que votre article créé sur le site du CAF ne soit pas validé par les responsables. Voici ci-dessous le message joint :</p>
            <p>&laquo;<i>".html_utf8(stripslashes($_POST['msg'] ?: '...')).'</i>&raquo;</p>
            <p>Article concernée : &laquo;<i>'.html_utf8($authorDatas['titre_article']).'</i>&raquo;, créé le '.date('d/m/Y', $authorDatas['tsp_crea_article']).'</p>
            <p>
                Pour gérer vos articles, rendez-vous sur votre profil :
                <a href="'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'profil/articles.html" title="">'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'profil/articles.html</a>
            </p>
            ';
    }
    $content_header = '';
    $content_footer = '';

    // PHPMAILER
    require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';

    $mail = new CAFPHPMailer(); // defaults to using php "mail()"
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

if (2 == $status_article) {
    header('Location: /gestion-des-articles.html');
    exit();
}
