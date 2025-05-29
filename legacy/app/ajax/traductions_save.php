<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$log = (isset($log) ? $log : '') . "\n accès à " . date('H:i:s');
$result = null;

if (isGranted(SecurityConstants::ROLE_ADMIN)) {
    $id_content_inline = (int) $_POST['id'];
    $log .= "\n id_content_inline :  " . $id_content_inline;
    $code_content_inline = $_POST['code_content_inline'];
    $contenu_content_inline = $_POST['contenu_content_inline'];
    $log .= "\n contenu_content_inline :  \n" . $contenu_content_inline;
    $contenu_content_inline = html_entity_decode($contenu_content_inline, \ENT_QUOTES, 'UTF-8');
    $log .= "\n html_entity_decode :  \n" . $contenu_content_inline;
    $groupe_content_inline = (int) $_POST['groupe_content_inline'];
    $log .= "\n groupe_content_inline :  \n" . $groupe_content_inline;
    $lang_content_inline = stripslashes(mb_convert_encoding($_POST['lang_content_inline'], 'UTF-8'));
    $log .= "\n lang_content_inline :  \n" . $lang_content_inline;
    $linkedtopage_content_inline = stripslashes(mb_convert_encoding($_POST['linkedtopage_content_inline'], 'UTF-8'));
    $log .= "\n linkedtopage_content_inline :  \n" . $linkedtopage_content_inline;

    if (isset($_POST['id_content_inline']) && isset($_POST['lang_content_inline']) && isset($_POST['contenu_content_inline'])) {

        // entrée à créer
        if (!$id_content_inline) {
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO  `caf_content_inline` (groupe_content_inline, code_content_inline ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`) VALUES (?, ?, ?, ?, ?, ?)");
            $current_time = time();
            $stmt->bind_param("isssis", $groupe_content_inline, $code_content_inline, $lang_content_inline, $contenu_content_inline, $current_time, $linkedtopage_content_inline);
        } else {
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("UPDATE  `caf_content_inline` SET  `contenu_content_inline` =  ?, `date_content_inline` =  ?  WHERE  `caf_content_inline`.`id_content_inline` = ? LIMIT 1 ;");
            $current_time = time();
            $stmt->bind_param("sii", $contenu_content_inline, $current_time, $id_content_inline);
        }
        $log .= "\n SQL : ";
        if (!$stmt->execute()) {
            $log .= "ERREUR : requête préparée";
        } else {
            $log .= "\n OK ";
            $result['success'] = true;
            $result['req'] = 'requête préparée';
            $result['content'] = stripslashes($contenu_content_inline);
        }
        $stmt->close();
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
