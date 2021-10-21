<?php

    $tab = explode('-', $p2);
    $token_user_mailchange = $tab[0];
    $id_user_mailchange = (int) ($tab[1]);

    // recup infos user par id donné
    if ($id_user_mailchange) {
        include SCRIPTS.'connect_mysqli.php';

        $found = false;
        $token_user_mailchange = $mysqli->real_escape_string($token_user_mailchange);
        $req = 'SELECT * FROM `'.$pbd."user_mailchange` WHERE `id_user_mailchange` = $id_user_mailchange AND `token_user_mailchange` LIKE '$token_user_mailchange' ORDER BY `time_user_mailchange` DESC LIMIT 1";
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $found = true;
            // req trouvé, verif : le lien doit avoir été cliqué dans l'heure...
            if (time($handle['time_user_mailchange']) > $p_time - (60 * 60)) {
                // maj du compte visé avec le nouveau email
                $req = 'UPDATE `'.$pbd."user` SET `email_user` = '".$handle['email_user_mailchange']."' WHERE `id_user` =".$handle['user_user_mailchange'].' LIMIT 1 ;';
                if (!$mysqli->query($req)) {
                    $errTab[] = 'Erreur SQL : updating user';
                }
                // suppression de la req
                $req = 'DELETE FROM `'.$pbd.'user_mailchange` WHERE `id_user_mailchange` = '.$handle['id_user_mailchange'].' LIMIT 1;';
                if (!$mysqli->query($req)) {
                    $errTab[] = 'Erreur SQL : deleting request';
                }
                // relogging
                user_login($handle['id_user_mailchange'], false);
            } else {
                $errTab[] = "Ce lien est obsolète : vous avez une heure pour cliquer sur le lien. Merci de redemander l'envoi d'un e-mail.";
            }
        }
        if (!$found) {
            $errTab[] = 'Cette requête est introuvable';
        }
        $mysqli->close();
    } else {
        $errTab[] = 'Erreur de données (id)';
    }
