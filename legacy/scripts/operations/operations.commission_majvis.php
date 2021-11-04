<?php

if (!allowed('comm_edit')) {
    $errTab[] = 'Vous n\'avez pas les droits nécessaires pour cette operation';
}
if (!(int) ($_POST['id_commission'])) {
    $errTab[] = 'Erreur à la reception des données';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
    $id_commission = (int) ($_POST['id_commission']);
    $vis_commission = (int) ($_POST['vis_commission']);
    $req = "UPDATE caf_commission SET vis_commission = $vis_commission WHERE id_commission = $id_commission LIMIT 1";
    if (!$mysqli->query($req)) {
        $errTab[] = 'Erreur SQL';
    }
    $mysqli->close;
}
