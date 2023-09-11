<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$destinataire = $expediteur = null;

$id_user = (int) $_POST['id_user'];
$nom = stripslashes($_POST['nom'] ?? '');
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
        WHERE id_user = '.getUser()->getId();

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $expediteur = $handle;
    }
    if (!$expediteur) {
        $errTab[] = 'Expediteur introuvable';
    }

    // dans ce cas, les valeurs sont réécrites
    $nom = $expediteur['civ_user'].' '.$expediteur['firstname_user'].' '.$expediteur['lastname_user'].' ('.$expediteur['nickname_user'].')';
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
    LegacyContainer::get('legacy_mailer')->send($destinataire['email_user'], 'transactional/contact-form', [
        'contact_name' => $nom,
        'contact_email' => $email,
        'contact_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'user-full/'.$expediteur['id_user'].'.html',
        'contact_objet' => $objet,
        'message' => $message,
    ], [], null, $email);
}

// tout s'est bien passé, on vide les variables postées
if (!isset($errTab) || 0 === count($errTab)) {
    unset($_POST);
    $_POST['operation'] = 'user_contact';
}
