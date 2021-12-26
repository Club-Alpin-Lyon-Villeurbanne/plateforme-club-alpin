<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$errTab = $errTabMail = [];
$id_transporteur = $id_destination = $destination = $encadrants = null;

if (!$_POST['transporteur']) {
    $errTab[] = 'Merci de sélectionner un transporteur.';
} else {
    $id_transporteur = trim(stripslashes($_POST['transporteur']));
}
if (!$_POST['id_destination'] || empty($_POST['id_destination'])) {
    $errTab[] = 'Identifiant destination manquant.';
} else {
    $id_destination = (int) ($_POST['id_destination']);
    $destination = get_destination($id_destination);
}
$mail_to_responsables = false;

if ($_POST['transporteur'] || 'on' == $_POST['transporteur']) {
    $mail_to_responsables = true;
    $encadrants = get_all_encadrants_destination($id_destination, false);
}

if ($destination['mail']) {
    $errTab[] = 'Les emails ont déjà été envoyés.';
}

if (0 === count($errTab)) {
    if (!$errTabMail && $mail_to_responsables) {
        foreach ($encadrants as $encadrant) {
            // recup de son email & nom
            $toMail = $encadrant['email_user'];
            $toName = $encadrant['firstname_user'];
            if (!isMail($toMail)) {
                $errTabMail[] = "Les coordonnées du contact $toMail sont erronées. Il ne sera pas alerté.";
            }

            if (0 === count($errTabMail)) {
                LegacyContainer::get('legacy_mailer')->send($toMail, 'transactional/destination-cloturee', [
                    'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$encadrant['sortie']['code_evt'].'-'.$encadrant['sortie']['id_evt'].'.html',
                    'event_name' => $encadrant['sortie']['titre_evt'],
                    'event_fiche' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'feuille-de-sortie/evt-'.$encadrant['sortie']['id_evt'].'.html',
                    'dest_fiche' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'feuille-de-sortie/dest-'.$id_destination.'.html',
                ]);
            }
        }
    }

    if (count($errTabMail)) {
        $errTab = array_merge($errTabMail, $errTab);
    }

    if (0 === count($errTab)) {
        $req = "UPDATE `caf_destination` SET `mail` = '1' WHERE `caf_destination`.`id` = $id_destination";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = "Les emails ont bien été envoyé, mais cette information n'a pas été enregistrée";
        }
    }
}
