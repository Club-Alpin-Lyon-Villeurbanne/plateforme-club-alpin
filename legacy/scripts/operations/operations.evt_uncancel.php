<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_evt = (int) substr(strrchr($p2, '-'), 1);

// checks
if (!$id_evt) {
    $errTab[] = 'ID invalide';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // recuperation de la sortie demandée
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT id_evt, code_evt, status_evt, titre_evt, tsp_evt, code_commission,
        id_user, civ_user, firstname_user, lastname_user, nickname_user, email_user
        FROM caf_evt, caf_user, caf_commission
        WHERE id_evt=?
        AND id_user = user_evt
        AND commission_evt=id_commission
        LIMIT 1");
    $stmt->bind_param("i", $id_evt);
    $stmt->execute();
    $handleSql = $stmt->get_result();

    if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // on a le droit d'annuler ?
        if (!(allowed('evt_cancel', 'commission:' . $handle['code_commission']) || allowed('evt_cancel_any'))) {
            $errTab[] = 'Accès non autorisé';
        } else {
            // mise à jour
            if (!isset($errTab) || 0 === count($errTab)) {
                $stmt2 = LegacyContainer::get('legacy_mysqli_handler')->prepare("UPDATE caf_evt SET cancelled_evt='0', cancelled_who_evt=null, cancelled_when_evt='0', status_evt='0' WHERE caf_evt.id_evt =?");
                $stmt2->bind_param("i", $id_evt);
                if (!$stmt2->execute()) {
                    $errTab[] = 'Erreur SQL';
                }
                $stmt2->close();

                LegacyContainer::get('legacy_mailer')->send($handle['email_user'], 'transactional/sortie-reactivee', [
                    'event_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $handle['code_evt'] . '-' . $handle['id_evt'] . '.html',
                    'event_name' => $handle['titre_evt'],
                ]);
            }

            // redirection vers la page de la sortie
            if (!isset($errTab) || 0 === count($errTab)) {
                header('Location: /sortie/' . $handle['code_evt'] . '-' . $handle['id_evt'] . '.html');
                exit;
            }
        }
    }
    $stmt->close();
}
