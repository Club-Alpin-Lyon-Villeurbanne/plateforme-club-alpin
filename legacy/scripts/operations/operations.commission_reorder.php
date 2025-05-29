<?php

use App\Legacy\LegacyContainer;

if (!allowed('comm_edit')) {
    $errTab[] = 'Vous n\'avez pas les droits nécessaires pour cette operation';
}
if (!count($_POST['id_commission'])) {
    $errTab[] = 'Erreur à la reception des données';
}

if (!isset($errTab) || 0 === count($errTab)) {
    for ($i = 0; $i < count($_POST['id_commission']); ++$i) {
        $id_commission = (int) $_POST['id_commission'][$i];
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("UPDATE caf_commission SET ordre_commission = ? WHERE id_commission = ? LIMIT 1");
        $stmt->bind_param("ii", $i, $id_commission);
        if (!$stmt->execute()) {
            $errTab[] = "Erreur SQL $i";
        }
        $stmt->close();
    }
}
