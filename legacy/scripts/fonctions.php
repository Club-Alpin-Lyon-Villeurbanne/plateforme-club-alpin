<?php

use App\Legacy\LegacyContainer;

function get_groupes($id_commission, $force_valid = false)
{
    $groupes = [];

    if (null == $id_commission) {
        return $groupes;
    }

    $req = 'SELECT * FROM `caf_groupe` WHERE `id_commission` = ' . $id_commission;
    if ($force_valid) {
        $req .= ' AND actif = 1 ';
    }
    $req .= ' ORDER BY `actif` DESC, `nom` ASC';
    $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $results->fetch_assoc()) {
        $groupes[$row['id']] = $row;
    }

    return $groupes;
}

function get_groupe($id_groupe)
{
    if (!$id_groupe || '' === trim($id_groupe)) {
        return false;
    }

    $groupe = false;

    $req = 'SELECT * FROM `caf_groupe` WHERE `id` = ' . $id_groupe;
    $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $groupe = $row;
        }
    }

    return $groupe;
}

function get_evt($id_evt)
{
    $evt = false;

    $req = 'SELECT * FROM `caf_evt` WHERE `id_evt` = ' . $id_evt;
    $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $results->fetch_assoc()) {
        $evt = $row;
    }

    return $evt;
}
