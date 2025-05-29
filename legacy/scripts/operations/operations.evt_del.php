<?php

use App\Legacy\LegacyContainer;

$id_evt = (int) substr(strrchr($p2, '-'), 1);

// checks
if (!$id_evt) {
    $errTab[] = 'ID invalide';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // recuperation de la sortie demandée
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt
                , title_commission, code_commission
        FROM caf_evt, caf_commission
        WHERE id_evt=?
        AND commission_evt=id_commission
        LIMIT 1");
    $stmt->bind_param("i", $id_evt);
    $stmt->execute();
    $handleSql = $stmt->get_result();

    if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // on a le droit d'annuler ?
        if (!allowed('evt_delete', 'commission:' . $handle['code_commission'])) {
            $errTab[] = 'Accès non autorisé';
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            // suppression inscrits
            $stmt2 = LegacyContainer::get('legacy_mysqli_handler')->prepare("DELETE FROM caf_evt_join WHERE caf_evt_join.evt_evt_join=?");
            $stmt2->bind_param("i", $id_evt);
            if (!$stmt2->execute()) {
                $errTab[] = 'Erreur SQL';
            }
            $stmt2->close();

            // suppression sortie principale et sortie associee
            $stmt3 = LegacyContainer::get('legacy_mysqli_handler')->prepare("DELETE FROM caf_evt WHERE caf_evt.id_evt=?");
            $stmt3->bind_param("i", $id_evt);
            if (!$stmt3->execute()) {
                $errTab[] = 'Erreur SQL';
            }
            $stmt3->close();
        }

        // redirection vers la page de la sortie avec le message "supprimée"
        if (!isset($errTab) || 0 === count($errTab)) {
            header('Location: /profil/sorties/self?lbxMsg=evt_deleted');
            exit;
        }
    }
    $stmt->close();
}
