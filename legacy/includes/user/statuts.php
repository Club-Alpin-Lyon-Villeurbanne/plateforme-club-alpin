<?php

use App\Legacy\LegacyContainer;

// liste des statuts
$row['statuts'] = [
    'club' => [],
    'commissions' => [],
];

$req = 'SELECT title_usertype, params_user_attr, description_user_attr
        FROM caf_user_attr, caf_usertype
        WHERE user_user_attr=' . $id_user . '
        AND id_usertype=usertype_user_attr
        ORDER BY hierarchie_usertype DESC, params_user_attr ASC
        LIMIT 50';
$handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($row2 = $handleSql2->fetch_assoc()) {
    $params = '';
    if (!empty($row2['params_user_attr'])) {
        $params = $row2['params_user_attr'];
    }
    $commissionCode = substr(strrchr($params, ':'), 1);
    if (!empty($commissionCode)) {
        $commission = '';

        $commReq = 'SELECT title_commission FROM caf_commission WHERE code_commission="' . $commissionCode . '" LIMIT 1';
        $commHandle = LegacyContainer::get('legacy_mysqli_handler')->query($commReq);
        while ($commRow = $commHandle->fetch_assoc()) {
            $commission = $commRow['title_commission'] ?? '';
        }

        $row['statuts']['commissions'][$commission][] = ['title' => $row2['title_usertype'], 'desc' => $row2['description_user_attr']];
    } else {
        $row['statuts']['club'][] = ['title' => $row2['title_usertype'], 'desc' => $row2['description_user_attr']];
    }
}
