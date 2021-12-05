<?php

global $kernel;

if (!allowed('comm_edit')) {
    $errTab[] = 'Vous n\'avez pas les droits nécessaires pour cette operation';
}
if (!count($_POST['id_commission'])) {
    $errTab[] = 'Erreur à la reception des données';
}

if (!isset($errTab) || 0 === count($errTab)) {
    for ($i = 0; $i < count($_POST['id_commission']); ++$i) {
        $id_commission = (int) ($_POST['id_commission'][$i]);
        $req = "UPDATE caf_commission SET ordre_commission = $i WHERE id_commission = $id_commission LIMIT 1";
        if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = "Erreur SQL $i";
        }
    }
}
