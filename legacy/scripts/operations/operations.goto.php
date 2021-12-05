<?php

global $kernel;

$destination = ['partenaire'];
$partenaire = null;

// CONTROLES
if (!in_array($p2, $destination, true)) {
    $errTab[] = 'destination inconnue';
}
if (!is_numeric($p3)) {
    $errTab[] = 'partenaire inconnu';
}

if (!isset($errTab) || 0 === count($errTab)) {
    switch ($p2) {
        case 'partenaire':
            $part_id = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($p3);
            //if (preg_match ('/Googlebot/i', $_SERVER['HTTP_USER_AGENT']===FALSE)) {
                // comptage si pas robot
                $req = "UPDATE caf_partenaires SET part_click=part_click+1 WHERE part_id = '$part_id' LIMIT 1";

                if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
                    $errTab[] = 'Erreur SQL';
                }
            //}
            $req = "SELECT part_url FROM caf_partenaires WHERE part_id = '$part_id' LIMIT 1";
            $result = $kernel->getContainer()->get('legacy_mysqli_handler')->query($req);

            if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }

            while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
                $partenaire = $handle;
            }

            if ('' !== $partenaire['part_url']) {
                header('Location:'.$partenaire['part_url']);
                exit;
            }
            break;
    }
}
