<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_evt = (int) ($_POST['id_evt']);
$user_evt = getUser()->getIdUser();
$objet = trim(stripslashes($_POST['objet']));
$message = trim(stripslashes($_POST['message']));

$status_sendmail = $titre_evt = $destTab = $destinataire = $code_evt = null;

switch ($_POST['status_sendmail'].'') {
    case '*':
        $status_sendmail = false; break;
    case '0':
    case '1':
    case '2':
        $status_sendmail = (int) ($_POST['status_sendmail']); break;
    default:
        $errTab[] = 'Merci de sélectionner les destinataires du message.';
}

// check
if (!$id_evt) {
    $errTab[] = 'Missing evt id';
}
if (strlen($objet) < 4) {
    $errTab[] = 'Veuillez entrer un objet de plus de 4 caractères';
}
if (strlen($message) < 10) {
    $errTab[] = 'Veuillez entrer un message valide';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // sélection de l'événement, avec vérification que j'EN SUIS L'AUTEUR, puis des users liés en fonction des destinataires demandés
    $req = "
    SELECT  `id_user` ,  `email_user` ,  `firstname_user` ,  `lastname_user` ,  `nickname_user` ,  `civ_user`
    FROM caf_user, caf_evt_join, caf_evt
    WHERE id_evt= $id_evt
    AND user_evt_join =id_user
    AND evt_evt_join =id_evt

    ".(false !== $status_sendmail ? " AND status_evt_join =$status_sendmail " : '');

    //		print ($req);exit;
    $destTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $destTab[] = $handle;
    }

    if (!count($destTab)) {
        $errTab[] = 'Aucun destinataire trouvé. Vérifiez la liste de destinataires choisie.';
    }
}

// créa, envoi du mail
if (!isset($errTab) || 0 === count($errTab)) {
    // infos evt
    $req = "SELECT * FROM caf_evt WHERE id_evt = $id_evt";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $titre_evt = $handle['titre_evt'];
        $code_evt = $handle['code_evt'];
        $id_evt = $handle['id_evt'];
    }

    $to = [];
    foreach ($destTab as $destinataire) {
        $to[$destinataire['email_user']] = sprintf('%s %s', $destinataire['firstname_user'], $destinataire['lastname_user']);
    }

    $author = getUser();

    LegacyContainer::get('legacy_mailer')->send($to, 'transactional/message-sortie', [
        'objet' => $objet,
        'message_author' => sprintf('%s %s', $author->getFirstnameUser(), $author->getLastnameUser()),
        'url_sortie' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$code_evt.'-'.$id_evt.'.html',
        'name_sortie' => $titre_evt,
        'message' => $message,
    ], [], $author, $author->getEmailUser());
}

// reset vals
if (!isset($errTab) || 0 === count($errTab)) {
    $_POST['objet'] = $_POST['message'] = '';
}
