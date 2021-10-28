<?php

    $destination = ['partenaire'];

    // CONTROLES
    if (!in_array($p2, $destination, true)) {
        $errTab[] = 'destination inconnue';
    }
    if (!is_numeric($p3)) {
        $errTab[] = 'partenaire inconnu';
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        include $scriptsDir.'connect_mysqli.php';
        switch ($p2) {
            case 'partenaire':
                $part_id = $mysqli->real_escape_string($p3);
                //if (preg_match ('/Googlebot/i', $_SERVER['HTTP_USER_AGENT']===FALSE)) {
                    // comptage si pas robot
                    $req = "UPDATE caf_partenaires SET part_click=part_click+1 WHERE part_id = '$part_id' LIMIT 1";

                    if (!$mysqli->query($req)) {
                        $errTab[] = "Erreur SQL $i";
                        error_log('Erreur SQL:'.$mysqli->error);
                    }
                //}
                $req = "SELECT part_url FROM caf_partenaires WHERE part_id = '$part_id' LIMIT 1";
                $result = $mysqli->query($req);

                if (!$mysqli->query($req)) {
                    $errTab[] = "Erreur SQL $i";
                    error_log('Erreur SQL:'.$mysqli->error);
                }

                while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
                    $partenaire = $handle;
                }

                $mysqli->close;
                if (strlen($partenaire['part_url']) > 0) {
                    header('Location:'.$partenaire['part_url']);
                    exit;
                }
                break;
        }
    }
