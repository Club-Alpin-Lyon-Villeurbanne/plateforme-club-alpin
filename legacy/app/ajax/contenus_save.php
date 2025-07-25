<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$log = (isset($log) ? $log : '') . "\n accès à " . date('H:i:s');
$log .= "\n TEST utf 8 : Смотрите эту страницу в России";

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    $result = [];

    $id_content_inline = (int) $_POST['id'];
    $log .= "\n id_content_inline :  " . $id_content_inline;
    $contenu_content_inline = $_POST['val'];
    $log .= "\n contenu_content_inline :  \n" . $contenu_content_inline;
    $contenu_content_inline = html_entity_decode($contenu_content_inline, \ENT_QUOTES, 'UTF-8');
    $log .= "\n html_entity_decode :  \n" . $contenu_content_inline;

    if ($id_content_inline && isset($_POST['val'])) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE  `caf_content_inline` SET  `contenu_content_inline` =  ? WHERE  `caf_content_inline`.`id_content_inline` = ? LIMIT 1 ;');
        $stmt->bind_param('si', $contenu_content_inline, $id_content_inline);
        if (!$stmt->execute()) {
            $result['error'] = 'SQL error : UPDATE caf_content_inline';
        } else {
            $result['success'] = true;
            $result['req'] = 'UPDATE caf_content_inline ...';
            $result['content'] = stripslashes($contenu_content_inline);
        }
        $stmt->close();
    } else {
        $result['error'] = 'id_content_inline missing or val not set';
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
