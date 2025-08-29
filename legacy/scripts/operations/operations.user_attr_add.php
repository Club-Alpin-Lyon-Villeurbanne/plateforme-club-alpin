<?php

use App\Legacy\LegacyContainer;

$needComm = false; // besoin, ou pas de spécifier la commission liée à ce type

// Vérification des variables données
$id_usertype = (int) $_POST['id_usertype'];
$id_user = (int) $_POST['id_user'];
$params_user_attr_tab = array_key_exists('commission', $_POST) ? $_POST['commission'] : [];
$description_user_attr = $_POST['description_user_attr'];
if (!$id_usertype || !$id_user) {
    $errTab[] = 'Valeurs manquantes';
}

/* checks manuels par rapport aux ID des droits exportés depuis la BDD
|1|0|visiteur|Visiteur|0
|2|10|adherent|Adhérent|0
|3|40|redacteur|Rédacteur|1
|4|60|encadrant|Encadrant|1
|5|70|responsable-commission|Resp. de commission|1
|6|90|president|Président|0
|7|80|vice-president|Vice Président|0
|8|100|administrateur|Administrateur|0
|9|20|salarie|Salarié|0
|10|30|benevole|Bénévole|1
|11|50|coencadrant|Co-encadrant|1
*/

// user_giveright_1
if (10 == $id_usertype && !allowed('user_giveright_1')) {
    $errTab[] = 'Vous ne disposez pas des droits nécessaires pour attribuer ce statut';
}
if (11 == $id_usertype && !allowed('user_giveright_1')) {
    $errTab[] = 'Vous ne disposez pas des droits nécessaires pour attribuer ce statut';
}
if (4 == $id_usertype && !allowed('user_giveright_1')) {
    $errTab[] = 'Vous ne disposez pas des droits nécessaires pour attribuer ce statut';
}

// user_giveright_2
if (9 == $id_usertype && !allowed('user_giveright_2')) {
    $errTab[] = 'Vous ne disposez pas des droits nécessaires pour attribuer ce statut';
}

// user_givepresidence
if (6 == $id_usertype && !allowed('user_givepresidence')) {
    $errTab[] = 'Vous ne disposez pas des droits nécessaires pour attribuer ce statut';
}
if (7 == $id_usertype && !allowed('user_givepresidence')) {
    $errTab[] = 'Vous ne disposez pas des droits nécessaires pour attribuer ce statut';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // Vérification dans la liste des types
    // + Ce type a t-il besoin de paramètres pour fonctionner ?
    $req = "SELECT * FROM caf_usertype WHERE id_usertype =$id_usertype LIMIT 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    // trouvé
    if (!$result->num_rows) {
        $errTab[] = 'Aucune entree de ce type';
    } else {
        while ($row = $result->fetch_assoc()) {
            $needComm = $row['limited_to_comm_usertype'];
        }
    }
}

// a t-on bien joint des paramètres ?
if ((!isset($errTab) || 0 === count($errTab)) && $needComm) {
    if (!count($params_user_attr_tab)) {
        $errTab[] = 'Vous devez spécifier au moins une commission pour ce statut.';
    }
}

// allez, enfin on intègre
if (!isset($errTab) || 0 === count($errTab)) {
    if (!$needComm) {
        $params_user_attr_tab = [''];
    }
    // pour chaque commission
    $description_user_attr = substr(LegacyContainer::get('legacy_mysqli_handler')->escapeString($description_user_attr), 0, 99);
    foreach ($params_user_attr_tab as $params_user_attr) {
        $params_user_attr = LegacyContainer::get('legacy_mysqli_handler')->escapeString($params_user_attr);

        // Cet attribut avec ces paramètres n'existe t-il pas déjà pour cet user ?
        $req = "SELECT COUNT(id_user_attr)
            FROM caf_user_attr
            WHERE user_user_attr=$id_user
            AND usertype_user_attr=$id_usertype
            AND params_user_attr LIKE '$params_user_attr' LIMIT 1";
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $row = $result->fetch_row();
        if (!$row[0]) {
            // ajout
            $req = "INSERT INTO caf_user_attr(user_user_attr ,usertype_user_attr ,params_user_attr ,details_user_attr, description_user_attr)
                                        VALUES ('$id_user', '$id_usertype', '$params_user_attr', '" . time() . "', " . (strlen($description_user_attr) > 0 ? ("'" . $description_user_attr . "'") : 'NULL') . ');';
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }
            LegacyContainer::get('legacy_user_right_service')->notifyUserAfterRightAdded($id_user, $id_usertype, $params_user_attr, getUser());
        }
    }
}

// log admin
if (!isset($errTab) || 0 === count($errTab)) {
    mylog('user_attr_add', "Attribution d'un nouveau droit (id=$id_usertype) à un user (id=$id_user)");
}
