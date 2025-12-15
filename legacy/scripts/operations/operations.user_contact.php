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
    $errTab[] = 'Veuillez entrer un message plus long';
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
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user, civ_user, firstname_user, lastname_user, email_user, nickname_user
        FROM caf_user
        WHERE id_user = ?');
    $user_id = getUser()->getId();
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
        $expediteur = $handle;
    }
    $stmt->close();
    if (!$expediteur) {
        $errTab[] = 'Expediteur introuvable';
    }

    // dans ce cas, les valeurs sont réécrites
    $nom = ucfirst($expediteur['firstname_user']) . ' ' . strtoupper($expediteur['lastname_user']) . ' (' . $expediteur['nickname_user'] . ')';
    $shortName = ucfirst($expediteur['firstname_user']) . ' ' . strtoupper($expediteur['lastname_user']);
    $email = $expediteur['email_user'];
}

// récup' infos destinataire
if (!isset($errTab) || 0 === count($errTab)) {
    $destinataire = false;
    // ce user autorise t-il le contact
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT civ_user, firstname_user, lastname_user, email_user
        FROM caf_user
        WHERE id_user = ?');
    $stmt->bind_param('i', $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
        $destinataire = $handle;
    }
    $stmt->close();
    if (!$destinataire) {
        $errTab[] = 'Destinataire introuvable';
    }
    if (!isMail($destinataire['email_user'])) {
        $errTab[] = 'E-mail de destinataire invalide';
    }
}

if (!empty($idEvent)) {
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT e.*, c.title_commission FROM caf_evt AS e INNER JOIN caf_commission AS c ON (c.id_commission = e.commission_evt) WHERE id_evt = ? LIMIT 1');
    $stmt->bind_param('i', $idEvent);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($eventRow = $result->fetch_assoc()) {
        $event = $eventRow;
    }
    $stmt->close();
}

if (!empty($idArticle)) {
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT * FROM caf_article WHERE id_article = ? LIMIT 1');
    $stmt->bind_param('i', $idArticle);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($articleRow = $result->fetch_assoc()) {
        $article = $articleRow;
    }
    $stmt->close();
}

if (!isset($errTab) || 0 === count($errTab)) {
    $eventLink = $articleLink = null;

    if ($event) {
        $eventLink = LegacyContainer::get('legacy_router')->generate('sortie', ['code' => html_utf8($event['code_evt']), 'id' => (int) $event['id_evt']], UrlGeneratorInterface::ABSOLUTE_URL);
    } elseif ($article) {
        $articleLink = LegacyContainer::get('legacy_router')->generate('article_view', ['code' => html_utf8($article['code_article']), 'id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL);
    }
    LegacyContainer::get('legacy_mailer')->send($destinataire['email_user'], 'transactional/contact-form', [
        'contact_name' => $nom,
        'contact_shortname' => $shortName,
        'contact_email' => $email,
        'contact_url' => LegacyContainer::get('legacy_router')->generate('user_full', ['id' => $expediteur['id_user']]),
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
