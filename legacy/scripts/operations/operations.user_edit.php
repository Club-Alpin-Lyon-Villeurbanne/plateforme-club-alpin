<?php

use App\Legacy\LegacyContainer;

$userTab = [];
$userTab['email_user'] = trim(stripslashes($_POST['email_user']));
$userTab['id_user'] = trim(stripslashes($_POST['id_user']));
$userTab['auth_contact_user'] = trim(stripslashes($_POST['auth_contact_user']));

// vérification du format des données
if (!isMail($userTab['email_user'])) {
    $errTab[] = 'Adresse e-mail invalide';
}

if (!isset($errTab) || 0 === count($errTab)) {

    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE `caf_user` SET email_user=?, auth_contact_user=? WHERE id_user=?');
    $stmt->bind_param('ssi', $userTab['email_user'], $userTab['auth_contact_user'], $userTab['id_user']);
    $stmt->execute();
    $stmt->close();
    $okTab[] = 'Mise à jour du compte';
}
