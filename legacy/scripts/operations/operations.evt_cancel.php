<?php

use App\Entity\EventParticipation;
use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_evt = (int) substr(strrchr($p2, '-'), 1);
$msg = trim(stripslashes($_POST['msg']));
$nomadMsg = []; // message spécial par raport aux nomades

// checks
if (!strlen($msg)) {
    $errTab[] = 'Veuillez entrer un message';
}
if (!$id_evt) {
    $errTab[] = 'ID invalide';
}

// recuperation de la sortie demandée
$req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt
            , nickname_user
            , title_commission, code_commission
    FROM caf_evt, caf_user, caf_commission
    WHERE id_evt=$id_evt
    AND id_user = user_evt
    AND commission_evt=id_commission
    LIMIT 1";
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // on a le droit d'annuler ?
    $isCurrentUserEncadrant = false;
    $idUser = 0;
    if (user()) {
        $idUser = getUser()->getId();
    }

    // participants:
    $id_evt_forjoins = (int) $handle['id_evt'];

    $handle['joins'] = [];
    $req = "SELECT id_evt_join, id_user, email_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user
            FROM caf_evt_join, caf_user
            WHERE evt_evt_join = $id_evt_forjoins
            AND user_evt_join = id_user
            LIMIT 300";
    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    foreach ($handle['joins'] as $join) {
        if (in_array($join['role_evt_join'], EventParticipation::ROLES_ENCADREMENT_ETENDU, true) && $join['id_user'] == $idUser) {
            $isCurrentUserEncadrant = true;
            break;
        }
    }

    if (!($idUser == $handle['user_evt']
        || $isCurrentUserEncadrant && allowed('evt_cancel_own')
        || allowed('evt_cancel', 'commission:' . $handle['code_commission'])
        || allowed('evt_cancel_any')
    )) {
        $errTab[] = 'Accès non autorisé';
    }

    // Mise à jour : annulation
    if (!isset($errTab) || 0 === count($errTab)) {
        $req = "UPDATE caf_evt SET cancelled_evt='1', cancelled_who_evt='" . getUser()->getId() . "', cancelled_when_evt='" . time() . "'  WHERE caf_evt.id_evt =$id_evt";

        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
    }

    // message aux participants si la sortie est annulée alors qu'elle est publiée
    if ((!isset($errTab) || 0 === count($errTab)) && 1 == $handle['status_evt']) {
        // desinscription des participants de la sortie
        if (!isset($errTab) || 0 === count($errTab)) {
            $req = "DELETE FROM caf_evt_join WHERE role_evt_join NOT IN ('encadrant', 'stagiaire', 'coencadrant') AND (caf_evt_join.evt_evt_join = $id_evt";

            $req .= ')';
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
        }

        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            if (!isMail($handle2['email_user'])) {
                $nomadMsg[] = $handle2['civ_user'] . ' ' . ucfirst($handle2['firstname_user']) . ' ' . strtoupper($handle2['lastname_user']) . ' - ' . $handle2['tel_user'] . ' - ' . $handle2['tel2_user'];

                continue;
            }

            LegacyContainer::get('legacy_mailer')->send($handle2['email_user'], 'transactional/sortie-annulation', [
                'event_name' => $handle['titre_evt'],
                'commission' => $handle['title_commission'],
                'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $handle['code_evt'] . '-' . (int) $handle['id_evt'] . '.html',
                'event_date' => date('d/m/Y', $handle['tsp_evt']),
                'cancel_user_name' => getUser()->getNickname(),
                'cancel_user_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'voir-profil/' . getUser()->getId() . '.html',
                'message' => $msg,
            ]);
        }
    }
    // redirection vers la page de la sortie avec le message "annulé"
    if (!isset($errTab) || 0 === count($errTab)) {
        // sans message d'avertissement nomades
        if (!count($nomadMsg)) {
            header('Location: /sortie/' . $handle['code_evt'] . '-' . $handle['id_evt'] . '.html');
        // echo 'nop';
        } else {
            header('Location: /sortie/' . $handle['code_evt'] . '-' . $handle['id_evt'] . '.html?lbxMsg=nomadMsg&nomadMsg=' . implode('****', $nomadMsg));
        }
    }
}
