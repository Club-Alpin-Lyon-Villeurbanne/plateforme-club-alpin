<?php

use App\Legacy\LegacyContainer;

$userTab = [];
$userTab['cafnum_user'] = trim(stripslashes($_POST['cafnum_user']));
$userTab['email_user'] = trim(stripslashes($_POST['email_user']));
$userTab['mdp_user'] = trim(stripslashes($_POST['mdp_user']));
$userTab['id_user'] = trim(stripslashes($_POST['id_user']));
$userTab['auth_contact_user'] = trim(stripslashes($_POST['auth_contact_user']));
$userTab['lastname_user'] = trim(stripslashes($_POST['lastname_user']));

// vérification du format des données
if (!isMail($userTab['email_user'])) {
    $errTab[] = 'Adresse e-mail invalide';
}
if ('' !== $userTab['mdp_user'] && (strlen($userTab['mdp_user']) < 6 || strlen($userTab['mdp_user']) > 12)) {
    $errTab[] = 'Le mot de passe doit faire de 6 à 12 caractères';
}

if (!preg_match('/^(N_D)?\d{12}$/', $userTab['cafnum_user'])) {
    $errTab[] = "Numéro d'adhérent invalide : " . $userTab['cafnum_user'];
}

if (!isset($errTab) || 0 === count($errTab)) {

    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE `caf_user` SET email_user=?, auth_contact_user=? WHERE cafnum_user=?');
    $stmt->bind_param('sss', $userTab['email_user'], $userTab['auth_contact_user'], $userTab['cafnum_user']);
    $stmt->execute();
    $stmt->close();
    $okTab[] = 'Mise à jour du compte';
}
