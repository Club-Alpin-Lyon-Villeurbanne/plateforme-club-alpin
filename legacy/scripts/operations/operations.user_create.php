<?php

use App\Utils\NicknameGenerator;

global $kernel;

$civ_user = trim(stripslashes($_POST['civ_user']));
$firstname_user = trim(stripslashes($_POST['firstname_user']));
$lastname_user = trim(stripslashes($_POST['lastname_user']));
$nickname_user = NicknameGenerator::generateNickname($firstname_user, $lastname_user);
$cafnum_user = trim(stripslashes($_POST['cafnum_user']));
$email_user = trim(stripslashes($_POST['email_user']));
$mdp_user = trim(stripslashes($_POST['mdp_user']));
$birthday_user = trim(stripslashes($_POST['birthday_user']));
$tel_user = trim(stripslashes($_POST['tel_user']));
$tel2_user = trim(stripslashes($_POST['tel2_user']));
$adresse_user = trim(stripslashes($_POST['adresse_user']));
$cp_user = trim(stripslashes($_POST['cp_user']));
$ville_user = trim(stripslashes($_POST['ville_user']));
$pays_user = trim(stripslashes($_POST['pays_user']));
$auth_contact_user = trim(stripslashes($_POST['auth_contact_user']));

// vérification du format des données
if ('' === $civ_user) {
    $errTab[] = "Merci d'entrer la civilité";
}
if (strlen($firstname_user) < 2) {
    $errTab[] = "Merci d'entrer un prenom valide";
}
if (strlen($lastname_user) < 2) {
    $errTab[] = "Merci d'entrer un nom de famille valide";
}
if (!isMail($email_user)) {
    $errTab[] = 'Adresse e-mail invalide';
}
if (strlen($mdp_user) < 8 || strlen($mdp_user) > 40) {
    $errTab[] = 'Le mot de passe doit faire de 8 à 40 caractères';
}
if ($mdp_user != $_POST['mdp_user_confirm']) {
    $errTab[] = 'Veuillez entrer deux fois le même mot de passe, sans espace';
}
// date de naissance :
if (!preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $birthday_user)) {
    $errTab[] = 'La date de naissance doit être au format jj/mm/aaaa.';
}

// formatage date anniversaire
if (!isset($errTab) || 0 === count($errTab)) {
    // tsp de début
    $tab = explode('/', $birthday_user);
    $birthday_user = mktime(0, 0, 0, $tab[1], $tab[0], $tab[2]);
}

if (!isset($errTab) || 0 === count($errTab)) {
    $civ_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($civ_user);
    $firstname_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($firstname_user);
    $lastname_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($lastname_user);
    $nickname_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($nickname_user);
    $cafnum_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($cafnum_user);
    $email_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($email_user);
    $mdp_user = $kernel->getContainer()->get('legacy_hasher_factory')->getPasswordHasher('login_form')->hash($mdp_user);
    $birthday_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($birthday_user);
    $tel_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($tel_user);
    $tel2_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($tel2_user);
    $adresse_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($adresse_user);
    $cp_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($cp_user);
    $ville_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($ville_user);
    $pays_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($pays_user);
    $auth_contact_user = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($auth_contact_user);

    // vérification anti doublon (seulement sur comptes confirmés)
    $req = "SELECT COUNT(id_user) FROM caf_user WHERE email_user LIKE '$email_user' AND valid_user=1";
    $result = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);
    $row = $result->fetch_row();
    if ($row[0]) {
        $errTab[] = 'Un compte validé existe déjà avec cette adresse e-mail. Avez-vous <a href="'.generateRoute('session_password_lost').'" class="fancyframe" title="">oublié le mot de passe ?</a>';
    } else {
        $req = "INSERT INTO `caf_user` (`id_user`, `email_user`, `mdp_user`, `cafnum_user`, `firstname_user`, `lastname_user`, `nickname_user`, `created_user`, `birthday_user`, `tel_user`, `tel2_user`, `adresse_user`, `cp_user`, `ville_user`, `pays_user`, `civ_user`, `moreinfo_user`, `auth_contact_user`, `valid_user`, `cookietoken_user`, `manuel_user`)
                            VALUES (NULL, '$email_user', '$mdp_user', '$cafnum_user', '$firstname_user', '$lastname_user', '$nickname_user', '".time()."', '$birthday_user', '$tel_user', '$tel2_user', '$adresse_user', '$cp_user', '$ville_user', '$pays_user', '$civ_user', '', '$auth_contact_user', '1', '', '1');";
        if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
    }
}
