<?php

global $kernel;

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
    $errTab[] = "Numéro d'adhérent invalide : ".$userTab['cafnum_user'];
}
if (0 != strlen($userTab['cafnum_user_new']) && ((!is_numeric($userTab['cafnum_user_new'])) || 12 != strlen($userTab['cafnum_user_new']))) {
    $errTab[] = "Nouveau numéro d'adhérent invalide : ".$userTab['cafnum_user_new'];
}

if (!isset($errTab) || 0 === count($errTab)) {
    // insertion SQL
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    if ($userTab['cafnum_user_new'] > 0) {
        // echange du numero d'adherent,
        $req = "UPDATE `caf_user` SET cafnum_user='".$mysqli->real_escape_string($userTab['cafnum_user'])."' WHERE cafnum_user='".$mysqli->real_escape_string($userTab['cafnum_user_new'])."' AND lastname_user='".$mysqli->real_escape_string($userTab['lastname_user'])."'";
        $okTab[] = "Changement du numéro d'adhérent (".$userTab['cafnum_user'].' -> '.$userTab['cafnum_user_new']."), la mise à jour de la date d'adhésion sera effective sous 24h.";
        if (!$mysqli->query($req)) {
            $errTab[] = "Echange du numéro d'adhérent en erreur : ".$mysqli->error;
        } else {
            $userTab['cafnum_user'] = $userTab['cafnum_user_new'];
        }
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "UPDATE `caf_user` SET email_user='".$mysqli->real_escape_string($userTab['email_user'])."',
            auth_contact_user='".$mysqli->real_escape_string($userTab['auth_contact_user'])."',
            cafnum_user='".$mysqli->real_escape_string($userTab['cafnum_user'])."'";

        $req .= "	WHERE id_user='".$mysqli->real_escape_string($userTab['id_user'])."'";

        if (!$mysqli->query($req)) {
            $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
                'error' => $mysqli->error,
                'file' => __FILE__,
                'line' => __LINE__,
                'sql' => $req,
            ]);
            $errTab[] = 'Erreur SQL';
        } else {
            $okTab[] = 'Mise à jour du compte';
        }
    }
}
