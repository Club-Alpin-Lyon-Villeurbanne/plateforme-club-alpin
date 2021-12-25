<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_evt = (int) ($_POST['id_evt']);
$status_legal_evt = (int) ($_POST['status_legal_evt']);

// checks
if (!$id_evt) {
    $errTab[] = "Erreur d'identifiant";
}
if (!allowed('evt_legal_accept')) {
    $errTab[] = 'Vous ne semblez pas autorisé à effectuer cette opération';
}

$authorDatas = $subject = $content_main = null;

// save
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "UPDATE caf_evt SET status_legal_evt='$status_legal_evt', status_legal_who_evt=".getUser()->getId()." WHERE caf_evt.id_evt =$id_evt";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL';
    }

    // récupération des infos user et evt
    $req = "SELECT id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user, id_evt, titre_evt, code_evt, tsp_evt FROM caf_user, caf_evt WHERE id_user=user_evt AND id_evt=$id_evt LIMIT 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $authorDatas = $row;
    }
    if (!$authorDatas) {
        $errTab[] = 'User or evt not found';
    }
}

if ((!isset($errTab) || 0 === count($errTab)) && (1 == $status_legal_evt || 2 == $status_legal_evt)) {
    if (1 == $status_legal_evt) {
        LegacyContainer::get('legacy_mailer')->send($authorDatas['email_user'], 'transactional/sortie-president-validee', [
            'event_name' => $authorDatas['titre_evt'],
            'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html',
            'event_date' => date('d/m/Y', $authorDatas['tsp_evt']),
        ]);
    }
    if (2 == $status_legal_evt) {
        LegacyContainer::get('legacy_mailer')->send($authorDatas['email_user'], 'transactional/sortie-president-refusee', [
            'event_name' => $authorDatas['titre_evt'],
            'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$authorDatas['code_evt'].'-'.$authorDatas['id_evt'].'.html',
            'event_date' => date('d/m/Y', $authorDatas['tsp_evt']),
        ]);
    }
}
