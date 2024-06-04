<?php

use App\Legacy\LegacyContainer;

$tab = explode('-', $p2);
$token_user_mailchange = $tab[0];
$id_user_mailchange = (int) $tab[1];

// recup infos user par id donné
if ($id_user_mailchange) {
    $found = false;
    $token_user_mailchange = LegacyContainer::get('legacy_mysqli_handler')->escapeString($token_user_mailchange);
    $req = "SELECT *, UNIX_TIMESTAMP(time_user_mailchange) as `timestamp` FROM `caf_user_mailchange` WHERE `id_user_mailchange` = $id_user_mailchange AND `token_user_mailchange` LIKE '$token_user_mailchange' ORDER BY `time_user_mailchange` DESC LIMIT 1";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $found = true;
        // req trouvé, verif : le lien doit avoir été cliqué dans l'heure...
        if ((int) $handle['timestamp'] > time() - (60 * 60)) {
            // maj du compte visé avec le nouveau email
            $req = "UPDATE `caf_user` SET `email_user` = '" . $handle['email_user_mailchange'] . "' WHERE `id_user` =" . $handle['user_user_mailchange'] . ' LIMIT 1 ;';
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL : updating user';
            }
            // suppression de la req
            $req = 'DELETE FROM `caf_user_mailchange` WHERE `id_user_mailchange` = ' . $handle['id_user_mailchange'] . ' LIMIT 1;';
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL : deleting request';
            }
        } else {
            $errTab[] = "Ce lien est obsolète : vous avez une heure pour cliquer sur le lien. Merci de redemander l'envoi d'un e-mail.";
        }
    }
    if (!$found) {
        $errTab[] = 'Cette requête est introuvable';
    }
} else {
    $errTab[] = 'Erreur de données (id)';
}
