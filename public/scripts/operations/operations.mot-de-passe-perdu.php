<?php

$tab = explode('-', $p2);
$token_user_mdpchange = $tab[0];
$id_user_mdpchange = (int) ($tab[1]);

// recup infos user par id donné
if ($id_user_mdpchange) {
    include SCRIPTS.'connect_mysqli.php';

    $found = false;
    $token_user_mdpchange = $mysqli->real_escape_string($token_user_mdpchange);
    $req = 'SELECT *, UNIX_TIMESTAMP(time_user_mdpchange) as `timestamp` FROM `'.$pbd."user_mdpchange` WHERE `id_user_mdpchange` = $id_user_mdpchange AND `token_user_mdpchange` LIKE '$token_user_mdpchange' ORDER BY `time_user_mdpchange` DESC LIMIT 1";
    $handleSql = $mysqli->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $found = true;
        // req trouvé, verif : le lien doit avoir été cliqué dans l'heure...
        if ((int) $handle['timestamp'] > time() - (60 * 60)) {
            // maj du compte visé avec le nouveau mdp et
            $req = 'UPDATE `'.$pbd."user` SET `mdp_user` = '".$handle['pwd_user_mdpchange']."' WHERE `id_user` =".$handle['user_user_mdpchange'].' LIMIT 1 ;';
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL : updating user';
            }
            // suppression de la req
            $req = 'DELETE FROM `'.$pbd.'user_mdpchange` WHERE `id_user_mdpchange` = '.$handle['id_user_mdpchange'].' LIMIT 1;';
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL : deleting request';
            }
        } else {
            $errTab[] = "Ce lien est obsolète : vous avez une heure pour confirmer le nouveau mot de passe. Merci de redemander l'envoi d'un e-mail.";
        }
    }
    if (!$found) {
        $errTab[] = 'Cette requête est introuvable';
    }
    $mysqli->close();
} else {
    $errTab[] = 'Erreur de données (id)';
}
