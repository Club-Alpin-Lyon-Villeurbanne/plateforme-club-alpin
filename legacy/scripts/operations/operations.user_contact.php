<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$destinataire = $expediteur = $event = $article = null;

$id_user = (int) $_POST['id_user'];
$idEvent = (int) $_POST['id_event'] ?? 0;
$idArticle = (int) $_POST['id_article'] ?? 0;
$nom = stripslashes($_POST['nom'] ?? '');
$shortName = $nom;
$email = stripslashes($_POST['email'] ?? '');
$objet = stripslashes($_POST['objet'] ?? '');
$message = stripslashes($_POST['message'] ?? '');

if (strlen($objet) < 4) {
    $errTab[] = 'Veuillez entrer un objet de plus de 4 caractères';
}
if (strlen($message) < 10) {
    $errTab[] = 'Veuillez entrer un message valide';
}

// vérifications si contact non-user
if (!user()) {
    if (strlen($nom) < 4) {
        $errTab[] = 'Veuillez entrer votre nom';
    }
    if (!isMail($email)) {
        $errTab[] = 'Veuillez entrer une adresse mail valide';
    }
}
// récupération des infos si contact user
else {
    $expediteur = false;
    // ce user autorise t-il le contact
    $req = 'SELECT id_user, civ_user, firstname_user, lastname_user, email_user, nickname_user
        FROM caf_user
        WHERE id_user = ' . getUser()->getId();

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $expediteur = $handle;
    }
    if (!$expediteur) {
        $errTab[] = 'Expediteur introuvable';
    }

    // dans ce cas, les valeurs sont réécrites
    $nom = $expediteur['civ_user'] . ' ' . $expediteur['firstname_user'] . ' ' . $expediteur['lastname_user'] . ' (' . $expediteur['nickname_user'] . ')';
    $shortName = $expediteur['firstname_user'] . ' ' . $expediteur['lastname_user'];
    $email = $expediteur['email_user'];
}

// récup' infos destinataire
if (!isset($errTab) || 0 === count($errTab)) {
    $destinataire = false;
    // ce user autorise t-il le contact
    $req = "SELECT civ_user, firstname_user, lastname_user, auth_contact_user, email_user
        FROM caf_user
        WHERE id_user = $id_user
        ";

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $destinataire = $handle;
    }
    if (!$destinataire) {
        $errTab[] = 'Destinataire introuvable';
    }
    if (!isMail($destinataire['email_user'])) {
        $errTab[] = 'E-mail de destinataire invalide';
    }
}

if (!empty($idEvent)) {
    $eventReq = "SELECT e.*, c.title_commission FROM caf_evt AS e INNER JOIN caf_commission AS c ON (c.id_commission = e.commission_evt) WHERE id_evt = $idEvent LIMIT 1";
    $eventResult = LegacyContainer::get('legacy_mysqli_handler')->query($eventReq);
    while ($eventRow = $eventResult->fetch_assoc()) {
        $event = $eventRow;
    }
}

if (!empty($idArticle)) {
    $articleReq = "SELECT * FROM caf_article WHERE id_article = $idArticle LIMIT 1";
    $articleResult = LegacyContainer::get('legacy_mysqli_handler')->query($articleReq);
    while ($articleRow = $articleResult->fetch_assoc()) {
        $article = $articleRow;
    }
}

// contact autorisé ? antipiratage
if (!isset($errTab) || 0 === count($errTab)) {
    $auth_contact_user = false;
    if ('none' == $destinataire['auth_contact_user']) {
        $errTab[] = 'Ce destinataire a désactivé le contact par e-mail.';
    }
    if ('users' == $destinataire['auth_contact_user'] && !user()) {
        $errTab[] = 'Vous devez être connecté pour contacter cette personne.';
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    $eventLink = $articleLink = null;

    if ($event) {
        $eventLink = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $event['code_evt'] . '-' . $event['id_evt'] . '.html';
    } elseif ($article) {
        $articleLink = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'article/' . $article['code_article'] . '-' . $article['id_article'] . '.html';
    }
    LegacyContainer::get('legacy_mailer')->send($destinataire['email_user'], 'transactional/contact-form', [
        'contact_name' => $nom,
        'contact_shortname' => $shortName,
        'contact_email' => $email,
        'contact_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-full/' . $expediteur['id_user'] . '.html',
        'contact_objet' => $objet,
        'message' => $message,
        'eventName' => $event ? $event['titre_evt'] : '',
        'eventLink' => $event ? $eventLink : '',
        'commission' => $event ? $event['title_commission'] : '',
        'articleTitle' => $article ? $article['titre_article'] : '',
        'articleLink' => $article ? $articleLink : '',
    ], [], null, $email);
}

// tout s'est bien passé, on vide les variables postées
if (!isset($errTab) || 0 === count($errTab)) {
    unset($_POST);
    $_POST['operation'] = 'user_contact';
}
