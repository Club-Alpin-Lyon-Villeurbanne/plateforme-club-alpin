<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
$parent_comment = (int) $_POST['parent_comment'];

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
    $sql = 'SELECT a.id_article, a.user_article, u.email_user, a.titre_article, a.code_article
            FROM caf_' . $parent_type_comment . ' a, caf_user u
            WHERE u.id_user=a.user_article
            AND a.id_' . $parent_type_comment . ' = ?
            AND a.status_' . $parent_type_comment . ' = 1
            LIMIT 1';
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($sql);
    $stmt->bind_param('i', $parent_comment);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    if (!$row[0]) {
        $errTab[] = "L'élément visé ne semble pas publié.";
    } else {
        $comment_article = $row;
    }
}

// insert SQL
if (!isset($errTab) || 0 === count($errTab)) {
    // article publié et commentable ?
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO caf_comment(status_comment, tsp_comment, user_comment, name_comment, email_comment, cont_comment, parent_type_comment, parent_comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $current_time = time();
    $user_id = getUser()->getId();
    $status = '1';
    $empty = '';
    $stmt->bind_param('siissssi', $status, $current_time, $user_id, $empty, $empty, $cont_comment, $parent_type_comment, $parent_comment);
    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL';
    }
    $stmt->close();

    if ('' !== $comment_article[2]) {
        LegacyContainer::get('legacy_mailer')->send($comment_article[2], 'transactional/article-comment', [
            'article_name' => $comment_article[3],
            'article_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'article/' . $comment_article[4] . '-' . $comment_article[0] . '.html#comments',
            'message' => $cont_comment,
        ], [], null, getUser()->getEmail());
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    $_POST['cont_comment'] = '';
}
