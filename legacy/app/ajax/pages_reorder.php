<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
$log = (isset($log) ? $log : '') . "\n accès à " . date('H:i:s');

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    $i = 1;
    foreach ($_GET['id'] as $id_page) {
        $log .= "\n GET id_page = $id_page";
        $id_page = (int) $id_page;
        if ($id_page) {
            $req = "UPDATE `caf_pdt` SET  `ordre_pdt` =  '" . $ordre_pdt . "' WHERE  `caf_pdt`.`id_pdt` =" . $id_pdt . ' LIMIT 1';
            $log .= "\n REQ : $req";
            LegacyContainer::get('legacy_mysqli_handler')->query($req);
            --$ordre_pdt;
        }
    }
}
