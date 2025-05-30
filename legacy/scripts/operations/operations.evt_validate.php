<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$subject = $content_main = $authorDatas = null;

$id_evt = (int) $_POST['id_evt'];
$status_evt = (int) $_POST['status_evt'];

// checks
if (!$id_evt) {
    $errTab[] = "Erreur d'identifiant";
}
if (!allowed('evt_validate')) {
    $errTab[] = 'Vous ne semblez pas autorisé à effectuer cette opération';
}

// save
if (!isset($errTab) || 0 === count($errTab)) {
    $user_id = getUser()->getId();
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_evt SET status_evt = ?, status_who_evt = ? WHERE caf_evt.id_evt = ?');
    $stmt->bind_param('iii', $status_evt, $user_id, $id_evt);
    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL';
    }
    $stmt->close();

    // récupération des infos user et evt
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, id_evt, titre_evt, code_evt, tsp_evt, title_commission FROM caf_user, caf_evt, caf_commission WHERE id_user = user_evt AND commission_evt = id_commission AND id_evt = ? LIMIT 1');
    $stmt->bind_param('i', $id_evt);
    $stmt->execute();
    $result = $stmt->get_result();
    $authorDatas = false;
    while ($row = $result->fetch_assoc()) {
        $authorDatas = $row;
    }
    $stmt->close();
    if (!$authorDatas) {
        $errTab[] = 'User or evt not found';
    }
}

// envoi de mail à l'auteur pour - lui confirmer la création / OU / l'informer du refus
if ((!isset($errTab) || 0 === count($errTab)) && (1 == $status_evt || 2 == $status_evt)) {
    // content vars
    if (1 == $status_evt) {
        LegacyContainer::get('legacy_mailer')->send($authorDatas['email_user'], 'transactional/sortie-publiee', [
            'event_name' => $authorDatas['titre_evt'],
            'commission' => $authorDatas['title_commission'],
            'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $authorDatas['code_evt'] . '-' . $authorDatas['id_evt'] . '.html',
            'event_date' => date('d/m/Y', $authorDatas['tsp_evt']),
        ]);
    }
    if (2 == $status_evt) {
        LegacyContainer::get('legacy_mailer')->send($authorDatas['email_user'], 'transactional/sortie-refusee', [
            'message' => stripslashes($_POST['msg'] ?: '...'),
            'event_name' => $authorDatas['titre_evt'],
            'commission' => $authorDatas['title_commission'],
            'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $authorDatas['code_evt'] . '-' . $authorDatas['id_evt'] . '.html',
            'event_date' => date('d/m/Y', $authorDatas['tsp_evt']),
        ]);
    }
}

if ((!isset($errTab) || 0 === count($errTab)) && 1 == $status_evt) {
    $handle['joins'] = [];
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, role_evt_join FROM caf_evt_join, caf_user WHERE evt_evt_join = ? AND status_evt_join = 1 AND user_evt_join = id_user AND id_user != ? LIMIT 300');
    $stmt->bind_param('ii', $id_evt, $authorDatas['id_user']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        LegacyContainer::get('legacy_mailer')->send($row['email_user'], 'transactional/sortie-publiee-inscrit', [
            'author_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'voir-profil/' . (int) $authorDatas['id_user'] . '.html',
            'author_nickname' => $authorDatas['nickname_user'],
            'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $authorDatas['code_evt'] . '-' . $authorDatas['id_evt'] . '.html',
            'event_name' => $authorDatas['titre_evt'],
            'event_date' => date('d/m/Y', $authorDatas['tsp_evt']),
            'commission' => $authorDatas['title_commission'],
            'role' => $row['role_evt_join'],
        ], [], null, $authorDatas['email_user']);
    }
    $stmt->close();
}
