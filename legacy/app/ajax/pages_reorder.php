<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$log = (isset($log) ? $log : '') . "\n accès à " . date('H:i:s');

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    $ordre_pdt = count($_GET['id']);
    foreach ($_GET['id'] as $id_page) {
        $log .= "\n GET id_page = $id_page";
        $id_page = (int) $id_page;
        if ($id_page) {
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE `caf_pdt` SET `ordre_pdt` = ? WHERE `caf_pdt`.`id_pdt` = ? LIMIT 1');
            $stmt->bind_param('ii', $ordre_pdt, $id_page);
            $stmt->execute();
            $stmt->close();
            $log .= "\n REQ : UPDATE caf_pdt ...";
            --$ordre_pdt;
        }
    }
}
