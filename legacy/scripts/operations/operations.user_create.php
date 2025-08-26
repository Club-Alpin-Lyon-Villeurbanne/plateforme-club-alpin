<?php

use App\Legacy\LegacyContainer;
use App\Utils\NicknameGenerator;

$civ_user = trim(stripslashes($_POST['civ_user']));
$firstname_user = ucfirst(trim(stripslashes($_POST['firstname_user'])));
$lastname_user = strtoupper(trim(stripslashes($_POST['lastname_user'])));
$nickname_user = NicknameGenerator::generateNickname($firstname_user, $lastname_user);
$cafnum_user = trim(stripslashes($_POST['cafnum_user']));
$email_user = trim(stripslashes($_POST['email_user']));
$mdp_user = trim(stripslashes($_POST['mdp_user']));
$birthday_user = trim(stripslashes($_POST['birthday_user']));
$tel_user = trim(stripslashes($_POST['tel_user'])) ?? null;
$tel2_user = trim(stripslashes($_POST['tel2_user']));
$adresse_user = trim(stripslashes($_POST['adresse_user']));
$cp_user = trim(stripslashes($_POST['cp_user']));
$ville_user = trim(stripslashes($_POST['ville_user']));
$pays_user = trim(stripslashes($_POST['pays_user']));
$auth_contact_user = trim(stripslashes($_POST['auth_contact_user'] ?? null));

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
if (strlen($mdp_user) < 8 || strlen($mdp_user) > 128) {
    $errTab[] = 'Le mot de passe doit faire de 8 à 128 caractères';
}
if ($mdp_user != $_POST['mdp_user_confirm']) {
    $errTab[] = 'Veuillez entrer deux fois le même mot de passe, sans espace';
}
// date de naissance :
if (!preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $birthday_user)) {
    $errTab[] = 'La date de naissance doit être au format jj/mm/aaaa.';
}

// vérification anti doublon de licence
$check_query = 'SELECT COUNT(*) FROM caf_user WHERE cafnum_user = ?';
$stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($check_query);
$stmt->bind_param('s', $cafnum_user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
if ($row[0] > 0) {
    $errTab[] = 'Un compte existe déjà avec ce numéro de licence.';
}
$stmt->close();

// formatage date anniversaire
if (!isset($errTab) || 0 === count($errTab)) {
    // tsp de début
    $tab = explode('/', $birthday_user);
    $birthday_user = mktime(0, 0, 0, $tab[1], $tab[0], $tab[2]);
}

if (!isset($errTab) || 0 === count($errTab)) {
    $mdp_user = LegacyContainer::get('legacy_hasher_factory')->getPasswordHasher('login_form')->hash($mdp_user);

    // vérification anti doublon d'email (seulement sur comptes confirmés)
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE email_user = ? AND valid_user = 1');
    $stmt->bind_param('s', $email_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    if ($row[0]) {
        $errTab[] = 'Un compte validé existe déjà avec cette adresse e-mail. Avez-vous <a href="' . generateRoute('session_password_lost') . '" class="fancyframe" title="">oublié le mot de passe ?</a>';
    } else {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO `caf_user` (`email_user`, `mdp_user`, `cafnum_user`, `firstname_user`, `lastname_user`, `nickname_user`, `created_user`, `birthday_user`, `tel_user`, `tel2_user`, `adresse_user`, `cp_user`, `ville_user`, `pays_user`, `civ_user`, `moreinfo_user`, `auth_contact_user`, `valid_user`, `cookietoken_user`, `manuel_user`, cafnum_parent_user, nomade_user, nomade_parent_user, doit_renouveler_user, alerte_renouveler_user)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, '1', '', '1', null, '0', '0', '0', '0')");
        $current_time = time();
        $stmt->bind_param('ssssssisssssssss', $email_user, $mdp_user, $cafnum_user, $firstname_user, $lastname_user, $nickname_user, $current_time, $birthday_user, $tel_user, $tel2_user, $adresse_user, $cp_user, $ville_user, $pays_user, $civ_user, $auth_contact_user);
        if (!$stmt->execute()) {
            $errTab[] = 'Erreur SQL';
        } else {
            // Synchroniser avec les services de marketing après création manuelle
            $new_user_id = LegacyContainer::get('legacy_mysqli_handler')->insertId();
            if ($new_user_id) {
                try {
                    $userRepository = LegacyContainer::get(App\Repository\UserRepository::class);
                    $user = $userRepository->find($new_user_id);

                    if ($user && $user->getEmail()) {
                        $emailMarketingService = LegacyContainer::get(App\Service\EmailMarketingSyncService::class);
                        $emailMarketingService->syncActivatedUser($user);
                    }
                } catch (Exception $e) {
                    // Log l'erreur mais ne pas bloquer la création
                    $logger = LegacyContainer::get('logger');
                    $logger->error('Failed to sync manually created user with email marketing services: ' . $e->getMessage());
                }
            }
        }
        $stmt->close();
    }
}
