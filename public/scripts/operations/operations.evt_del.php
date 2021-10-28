<?php

    $id_evt = (int) (substr(strrchr($p2, '-'), 1));

    // checks
    if (!$id_evt) {
        $errTab[] = 'ID invalide';
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        // recuperation de la sortie demandée
        include SCRIPTS.'connect_mysqli.php';
        $req = "SELECT id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
					, title_commission, code_commission
			FROM caf_evt, caf_commission
			WHERE id_evt=$id_evt
			AND commission_evt=id_commission
			LIMIT 1";
        $handleSql = $mysqli->query($req);

        if ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // on a le droit d'annuler ?
            if (!allowed('evt_delete', 'commission:'.$handle['code_commission'])) {
                $errTab[] = 'Accès non autorisé';
            }

            if (!isset($errTab) || 0 === count($errTab)) {
                // suppression inscrits
                $req = "DELETE FROM caf_evt_join WHERE caf_evt_join.evt_evt_join=$id_evt OR caf_evt_join.evt_evt_join IN (SELECT DISTINCT id_evt FROM caf_evt WHERE cycle_parent_evt = $id_evt)";
                if (!$mysqli->query($req)) {
                    $errTab[] = 'Erreur SQL (DELETE FROM caf_evt_join)';
                }

                // suppression sortie principale et sortie associee
                $req = "DELETE FROM caf_evt WHERE caf_evt.id_evt=$id_evt OR caf_evt.cycle_parent_evt=$id_evt";
                if (!$mysqli->query($req)) {
                    $errTab[] = 'Erreur SQL (DELETE FROM caf_evt)';
                }

                // gestion des sorties associées (cycle)
                //$req="UPDATE caf_evt SET caf_evt.cycle_parent_evt=NULL WHERE caf_evt.cycle_parent_evt=$id_evt";
                //if(!$mysqli->query($req)) $errTab[]="Erreur SQL (UPDATE caf_evt)";
            }

            $mysqli->close();

            // redirection vers la page de la sortie avec le message "supprimée"
            if (!isset($errTab) || 0 === count($errTab)) {
                header('Location:'.$p_racine.'profil/sorties/self.html?lbxMsg=evt_deleted');
                exit;
            }
        }
        $mysqli->close();
    }
