<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$lastname_user = trim(stripslashes($_POST['lastname_user']));
$cafnum_user = preg_replace('/\s+/', '', stripslashes($_POST['cafnum_user']));
$email_user = strtolower(trim(stripslashes($_POST['email_user'])));
$mdp_user = trim(stripslashes($_POST['mdp_user']));

if (strlen($lastname_user) < 2) {
    $errTab[] = 'Merci de renseigner un nom de famille valide';
}
if (12 != strlen($cafnum_user)) {
    $errTab[] = "Merci de renseigner un numéro d'adhérent CAF valide (12 chiffres)";
}
if (!isMail($email_user)) {
    $errTab[] = 'Merci de renseigner une adresse e-mail valide';
}
if (strlen($mdp_user) < 8 || strlen($mdp_user) > 128) {
    $errTab[] = 'Le mot de passe doit faire de 8 à 128 caractères';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $mdp_user = LegacyContainer::get('legacy_hasher_factory')->getPasswordHasher('login_form')->hash($mdp_user);

    // Si ce compte est déjà existant et activé avec ce numéro de licence
    if (!isset($errTab) || 0 === count($errTab)) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE cafnum_user = ? AND valid_user = 1 LIMIT 1');
        $stmt->bind_param('s', $cafnum_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        if ($row[0]) {
            $errTab[] = "Ce numéro d'adhérent correspond déjà à une inscription sur le site. Si vous avez perdu vos identifiants, utilisez le lien <i>Mot de passe oublié</i> ci-contre à droite.";
        }
    }

    // Si ce compte est déjà existant et activé avec cette adresse email
    if (!isset($errTab) || 0 === count($errTab)) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE email_user LIKE ? AND valid_user = 1 LIMIT 1');
        $stmt->bind_param('s', $email_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        if ($row[0]) {
            $errTab[] = 'Cette adresse e-mail correspond déjà à une inscription sur le site. Si vous avez perdu vos identifiants, utilisez le lien <i>Mot de passe oublié</i> ci-contre à droite.';
        }
    }

    // vérification du numéro CAF
    if (!isset($errTab) || 0 === count($errTab)) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE cafnum_user = ? LIMIT 1');
        $stmt->bind_param('s', $cafnum_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        if (!$row[0]) {
            $errTab[] = "Désolé, nous ne trouvons pas ce numéro d'adhérent dans notre base de données. Si vous venez de vous (ré)inscrire au CAF, nous vons invitons à réessayer ultérieurement.";
        }
    }

    // vérification de l'obsolescence du compte
    if (!isset($errTab) || 0 === count($errTab)) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE cafnum_user = ? AND doit_renouveler_user = 1 LIMIT 1');
        $stmt->bind_param('s', $cafnum_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        if ($row[0]) {
            $errTab[] = 'La licence pour ce compte semble être expirée. Si vous venez de renouveler votre licence nous vous invitons à réessayer ultérieurement.';
        }
    }

    $id_user = $nickname_user = $cookietoken_user = $firstname_user = null;

    // le nom colle ?
    if (!isset($errTab) || 0 === count($errTab)) {
        $id_user = false;
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT id_user FROM caf_user WHERE cafnum_user = ? AND upper(lastname_user) LIKE ? ORDER BY id_user DESC LIMIT 1');
        $lastname_upper = strtoupper($lastname_user);
        $stmt->bind_param('ss', $cafnum_user, $lastname_upper);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $id_user = $row['id_user'];
        }
        $stmt->close();
        if (!$id_user) {
            $errTab[] = "Le nom que vous avez entré ne correspond pas au numéro d'adhérent dans notre base de données.";
        }
    }

    // création du pseudonyme
    if (!isset($errTab) || 0 === count($errTab)) {
        $nickname_user = false;
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT lastname_user, firstname_user FROM caf_user WHERE id_user = ? LIMIT 1');
        $stmt->bind_param('i', $id_user);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $firstname_user = ucfirst(mb_strtolower($row['firstname_user'], 'UTF-8'));
            $nickname_user = str_replace([' ', '-', "'"], '', $firstname_user . substr(strtoupper($row['lastname_user']), 0, 1));
        }
        $stmt->close();
        if (!$nickname_user) {
            $errTab[] = 'Impossible de générer le pseudo. Merci de nous contacter.';
        }
    }

    // tt ok ? activation
    // intégration des valeurs données et du token nécessaire à la confirmation par email
    if (!isset($errTab) || 0 === count($errTab)) {
        $cookietoken_user = bin2hex(random_bytes(16));
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_user SET email_user = ?, mdp_user = ?, nickname_user = ?, updated_at = NOW(), cookietoken_user = ? WHERE id_user = ? LIMIT 1');
        $stmt->bind_param('sssssi', $email_user, $mdp_user, $nickname_user, $cookietoken_user, $id_user);
        if (!$stmt->execute()) {
            $errTab[] = 'Erreur de sauvegarde';
        }
        $stmt->close();
    }

    // envoi de l'e-mail
    if (!isset($errTab) || 0 === count($errTab)) {
        // check-in vars : string à retourner lors de la confirmation= md5 de la concaténation id-email
        $url = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-confirm/' . $cookietoken_user . '-' . $id_user . '.html';

        LegacyContainer::get('legacy_mailer')->send(stripslashes($email_user), 'transactional/compte-validation', [
            'url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-confirm/' . $cookietoken_user . '-' . $id_user . '.html',
        ]);
    }
}
