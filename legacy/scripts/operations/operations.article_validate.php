<?php

use App\Legacy\LegacyContainer;
use App\Messenger\Message\ArticlePublie;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_article = (int) $_POST['id_article'];
$status_article = (int) $_POST['status_article'];

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
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_article SET status_article=?, status_who_article=?, topubly_article = 0 WHERE caf_article.id_article =?');
    $user_id = getUser()->getId();
    $stmt->bind_param('iii', $status_article, $user_id, $id_article);

    LegacyContainer::get('legacy_message_bus')->dispatch(new ArticlePublie($id_article));

    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL';
    }
    $stmt->close();

    $stmt2 = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_article SET tsp_validate_article=? WHERE caf_article.id_article=? AND (tsp_validate_article=0 OR tsp_validate_article IS NULL)'); // premiere validation
    $current_time = time();
    $stmt2->bind_param('ii', $current_time, $id_article);
    if (!$stmt2->execute()) {
        $errTab[] = 'Erreur SQL';
    }
    $stmt2->close();

    // récupération des infos user et article
    $stmt3 = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, id_article, titre_article, code_article, tsp_crea_article, tsp_article FROM caf_user, caf_article WHERE id_user=user_article AND id_article=? LIMIT 1');
    $stmt3->bind_param('i', $id_article);
    $stmt3->execute();
    $result = $stmt3->get_result();
    while ($row = $result->fetch_assoc()) {
        $authorDatas = $row;
    }
    $stmt3->close();
    if (!$authorDatas) {
        $errTab[] = 'User or article not found';
    }

    $sql = 'SELECT (COUNT(id_article) - 5) as total FROM caf_article WHERE status_article = 1 AND une_article = 1';
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $limit = max(0, $row['total']);

        $sql = 'UPDATE caf_article SET une_article = 0 WHERE status_article = 1 AND une_article = 1 ORDER BY tsp_article ASC LIMIT ' . $limit;
        LegacyContainer::get('legacy_mysqli_handler')->query($sql);
    }
}

if ((!isset($errTab) || 0 === count($errTab)) && (1 == $status_article || 2 == $status_article)) {
    if (1 == $status_article) {
        LegacyContainer::get('legacy_mailer')->send($authorDatas['email_user'], 'transactional/article-valide', [
            'article_name' => $authorDatas['titre_article'],
            'article_url' => LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $authorDatas['code_article'], 'id' => $authorDatas['id_article']], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
    if (2 == $status_article) {
        LegacyContainer::get('legacy_mailer')->send($authorDatas['email_user'], 'transactional/article-refuse', [
            'article_name' => $authorDatas['titre_article'],
            'article_url' => LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $authorDatas['code_article'], 'id' => $authorDatas['id_article']], UrlGeneratorInterface::ABSOLUTE_URL),
            'message' => stripslashes($_POST['msg'] ?: '...'),
        ]);
    }
}

if (2 == $status_article) {
    header('Location: /gestion-des-articles.html');
    exit;
}
