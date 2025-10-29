<?php

use App\Legacy\ImageManipulator;
use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_user = $email_user_mailchange = null;

// check user online
if (!user()) {
    $errTab[] = "Vous avez été déconnecté. L'opération n'a pas été effectuée.";
}

// mise à jour infos texte
if (!isset($errTab) || 0 === count($errTab)) {
    $id_user = getUser()->getId();
    $tel_user = trim(stripslashes($_POST['tel_user'] ?? ''));
    $tel2_user = trim(stripslashes($_POST['tel2_user'] ?? ''));
    $adresse_user = trim(stripslashes($_POST['adresse_user'] ?? ''));
    $cp_user = trim(stripslashes($_POST['cp_user'] ?? ''));
    $ville_user = trim(stripslashes($_POST['ville_user'] ?? ''));
    $pays_user = trim(stripslashes($_POST['pays_user'] ?? ''));
    $email_user_mailchange = trim(stripslashes($_POST['email_user_mailchange'] ?? ''));

    if (!$id_user) {
        $errTab[] = 'Erreur technique : ID manquant';
    }
    if (!isMail($email_user_mailchange) && '' !== $email_user_mailchange) {
        $errTab[] = 'Vous avez demandé à remplacer votre adresse e-mail, mais elle semble invalide.';
    }
}

// mise à jour de la photo si transmise
if ((!isset($errTab) || 0 === count($errTab)) && $_FILES['photo']['size'] > 0) {
    if ($_FILES['photo']['error'] > 0) {
        $errTab[] = "Erreur dans l'image : " . $_FILES['photo']['error'];
    } else {
        // déplacement du fichier dans le dossier transit
        $uploaddir = __DIR__ . '/../../../public/ftp/transit/profil/';
        LegacyContainer::get('legacy_fs')->mkdir($uploaddir);
        $i = 1;
        while (file_exists($uploaddir . $i . '-profil.jpg')) {
            ++$i;
        }
        $filename = $i . '-profil.jpg';
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploaddir . $filename)) {
            $rep_Dst = __DIR__ . '/../../../public/ftp/user/' . $id_user . '/';

            $profilePic = $rep_Dst . 'profil.jpg';
            $uploadedFile = $uploaddir . $filename;

            if (!file_exists($rep_Dst)) {
                if (!mkdir($rep_Dst, 0755, true) && !is_dir($rep_Dst)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $rep_Dst));
                }
            }

            if (!ImageManipulator::resizeImage(1000, 1000, $uploadedFile, $profilePic)) {
                $errTab[] = 'Impossible de redimensionner la grande image';
            }

            $profilePicMin = $rep_Dst . 'min-profil.jpg';
            $profilePicPic = $rep_Dst . 'pic-profil.jpg';

            if (!ImageManipulator::resizeImage(150, 150, $uploadedFile, $profilePicMin)) {
                $errTab[] = 'Impossible de redimensionner la miniature';
            }

            if (!ImageManipulator::cropImage(55, 55, $uploadedFile, $profilePicPic)) {
                $errTab[] = 'Impossible de croper l\'image (picto)';
            }

            if (file_exists($uploaddir . $filename)) {
                unlink($uploaddir . $filename);
            }
        } else {
            $errTab[] = 'Erreur lors du déplacement du fichier';
        }
    }
}

// si mise à jour e-mail user
if ('' !== $email_user_mailchange) {
    $token = $id_user_mailchange = null;

    // VERIFICATIONS
    // compte des entrées existantes avec cet e-mail
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(id_user) FROM caf_user WHERE email_user LIKE ? AND id_user != ?');
    $stmt->bind_param('si', $email_user_mailchange, $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    if (getArrayFirstValue($result->fetch_array(\MYSQLI_NUM))) {
        $errTab[] = "Votre demande de modification d'e-mail est refusée : Un compte existe déjà avec cette adresse e-mail.";
    }
    $stmt->close();

    // ENTRÉE DE LA DEMANDE DANS LA BD
    if (!isset($errTab) || 0 === count($errTab)) {
        $token = bin2hex(random_bytes(16));
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('INSERT INTO `caf_user_mailchange` (`user_user_mailchange`, `token_user_mailchange`, `email_user_mailchange`) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $id_user, $token, $email_user_mailchange);
        if (!$stmt->execute()) {
            $errTab[] = 'Erreur SQL';
        } else {
            $id_user_mailchange = LegacyContainer::get('legacy_mysqli_handler')->insertId();
        }
        $stmt->close();
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        LegacyContainer::get('legacy_mailer')->send($email_user_mailchange, 'transactional/email-update', [
            'email' => $email_user_mailchange,
            'url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'email-change/' . $token . '-' . $id_user_mailchange . '.html',
        ]);
    }
}
