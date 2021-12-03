<?php

$id_user = null;

if ($p2) {
    $tab = explode('-', $p2);
    $cookietoken_user = $tab[0];
    $id_user = (int) ($tab[1]);

    // validation user
    if ($id_user) {
        $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
        $cookietoken_user = $mysqli->real_escape_string($cookietoken_user);

        $req = "UPDATE caf_user SET valid_user=1 WHERE  `id_user` = $id_user AND cookietoken_user LIKE '$cookietoken_user' LIMIT 1";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur de requete';
        } else {
            if (!$mysqli->affected_rows) {
                $errTab[] = 'Activation impossible : ce compte est introuvable, ou a déjà été validé.';
            } else {
                $req = "UPDATE caf_user c1
                    JOIN caf_user c2 ON c1.cafnum_parent_user = c2.cafnum_user
                    SET	c1.email_user=c2.email_user, c1.valid_user=1
                    WHERE c2.id_user=$id_user AND c1.valid_user=0 AND (c1.email_user IS NULL OR c1.email_user='')";
            }
        }
    } else {
        $errTab[] = 'Erreur de données (id)';
    }
} else {
    $errTab[] = 'Erreur de données (datas)';
}
