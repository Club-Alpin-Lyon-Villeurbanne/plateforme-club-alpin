<?php

use App\Legacy\LegacyContainer;

$userTab = [];
$userTab['cafnum_user'] = trim(stripslashes($_POST['cafnum_user']));
$userTab['cafnum_user_new'] = trim(stripslashes($_POST['cafnum_user_new']));
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

if (is_numeric(1 != $userTab['cafnum_user']) || 12 != strlen($userTab['cafnum_user'])) {
    $errTab[] = "Numéro d'adhérent invalide : " . $userTab['cafnum_user'];
}
if (0 != strlen($userTab['cafnum_user_new']) && ((!is_numeric($userTab['cafnum_user_new'])) || 12 != strlen($userTab['cafnum_user_new']))) {
    $errTab[] = "Nouveau numéro d'adhérent invalide : " . $userTab['cafnum_user_new'];
}

if (!isset($errTab) || 0 === count($errTab)) {
    if ($userTab['cafnum_user_new'] > 0) {
        LegacyContainer::get('legacy_member_merger')->mergeMembers($userTab['cafnum_user'], $userTab['cafnum_user_new']);
        $userTab['cafnum_user'] = $userTab['cafnum_user_new'];
    }

    $req = "UPDATE `caf_user` SET email_user='" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($userTab['email_user']) . "',
            auth_contact_user='" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($userTab['auth_contact_user']) . "'";

    $req .= "WHERE cafnum_user='" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($userTab['cafnum_user']) . "'";

    LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $okTab[] = 'Mise à jour du compte';
}
