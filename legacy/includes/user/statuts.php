<?php

use App\Legacy\LegacyContainer;

// liste des statuts
$row['statuts'] = [
    'club' => [],
    'commissions' => [],
];

$req = 'SELECT title_usertype, params_user_attr
        FROM caf_user_attr, caf_usertype
        WHERE user_user_attr=' . $id_user . '
        AND id_usertype=usertype_user_attr
        ORDER BY hierarchie_usertype DESC, params_user_attr ASC
        LIMIT 50';
$handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($row2 = $handleSql2->fetch_assoc()) {
    $commissionCode = substr(strrchr($row2['params_user_attr'], ':'), 1);
    if (!empty($commissionCode)) {
        $commission = '';

        $commReq = 'SELECT title_commission FROM caf_commission WHERE code_commission="' . $commissionCode . '" LIMIT 1';
        $commHandle = LegacyContainer::get('legacy_mysqli_handler')->query($commReq);
        while ($commRow = $commHandle->fetch_assoc()) {
            $commission = $commRow['title_commission'] ?? '';
        }

        $row['statuts']['commissions'][$commission][] = $row2['title_usertype'];
    } else {
        $row['statuts']['club'][] = $row2['title_usertype'];
    }
}
