<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_destination = (int) (substr(strrchr($p3, '-'), 1));
$msg = trim(stripslashes($_POST['msg']));
$nomadMsg = []; // message spécial par raport aux nomades

// checks
if (!strlen($msg)) {
    $errTab[] = 'Veuillez entrer un message';
}
if (!$id_destination) {
    $errTab[] = 'ID invalide';
}

// recuperation de la sortie demandée
$destination = get_destination($id_destination);

// on a le droit d'annuler ?
if (allowed('destination_supprimer')
    || (user() && $destination['id_user_who_create'] == (string) getUser()->getId())
    || (user() && $destination['id_user_responsable'] == (string) getUser()->getId())
    || (user() && $destination['id_user_adjoint'] == (string) getUser()->getId())
) {
} else {
    $errTab[] = 'Accès non autorisé';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $req = "UPDATE `caf_destination` SET `annule` = '1' WHERE `id` = $id_destination;";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL annulation destination';
    }
}

// Mise à jour : annulation des sorties
if (!isset($errTab) || 0 === count($errTab)) {
    $sorties = get_sorties_for_destination($id_destination);

    foreach ($sorties as $sortie) {
        $req = "UPDATE caf_evt SET cancelled_evt='1', cancelled_who_evt='".getUser()->getId()."', cancelled_when_evt='".time()."'  WHERE id_evt = ".$sortie['id_evt'];
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
    }
}

    // message aux participants si la sortie est annulée alors qu'elle est publiée
    if ((!isset($errTab) || 0 === count($errTab)) && 1 == $destination['publie']) {
        $destination['joins'] = [];
        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, nomade_user
                    , role_evt_join
                FROM caf_evt_join, caf_user
                WHERE id_destination = $id_destination
                AND user_evt_join = id_user
                LIMIT 500";
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // desinscription des participants de la sortie
        if (!isset($errTab) || 0 === count($errTab)) {
            $req = "DELETE FROM caf_evt_join WHERE role_evt_join NOT IN ('encadrant', 'coencadrant') AND id_destination = $id_destination";
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
        }

        $users = [];

        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            if (isMail($handle2['email_user'])) {
                $users[] = $handle2['email_user'];
            } else {
                $nomadMsg[] = $handle2['civ_user'].' '.$handle2['firstname_user'].' '.$handle2['lastname_user'].' - '.$handle2['tel_user'].' - '.$handle2['tel2_user'];
            }
        }

        LegacyContainer::get('legacy_mailer')->send($users, 'transactional/destination-annulee', [
            'cancel_user_name' => getUser()->getNickname(),
            'cancel_user_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'voir-profil/'.getUser()->getId().'.html',
            'dest_date' => display_date($destination['date']),
            'dest_name' => $destination['nom'],
            'dest_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'destination/'.html_utf8($destination['code']).'-'.(int) ($destination['id']).'.html',
            'message' => $msg,
        ]);
    }

    // redirection vers la page de la sortie avec le message "annulé"
    if (!isset($errTab) || 0 === count($errTab)) {
        // sans message d'avertissement nomades
        if (!count($nomadMsg)) {
            header('Location: /destination/'.$destination['code'].'-'.$destination['id'].'.html');
        // echo 'nop';
        } else {
            header('Location: /destination/'.$destination['code_evt'].'-'.$destination['id'].'.html?lbxMsg=nomadMsg&nomadMsg='.(implode('****', $nomadMsg)));
        }
    }
