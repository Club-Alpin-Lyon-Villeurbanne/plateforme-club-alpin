<?php

$log = (isset($log) ? $log : '')."\n accès à ".date('H:i:s');
$log .= "\n TEST utf 8 : Смотрите эту страницу в России";

if (admin()) {
    $result = [];

    include SCRIPTS.'connect_mysqli.php';

    $id_content_inline = (int) ($_POST['id']);
    $log .= "\n id_content_inline :  ".$id_content_inline;
    $contenu_content_inline = $_POST['val'];
    $log .= "\n contenu_content_inline :  \n".$contenu_content_inline;
    $contenu_content_inline = html_entity_decode($contenu_content_inline, \ENT_QUOTES, 'UTF-8');
    $log .= "\n html_entity_decode :  \n".$contenu_content_inline;

    if ($id_content_inline && isset($_POST['val'])) {
        $contenu_content_inline = $mysqli->real_escape_string($contenu_content_inline);
        $req = 'UPDATE  `'.$pbd."content_inline` SET  `contenu_content_inline` =  '$contenu_content_inline' WHERE  `".$pbd."content_inline`.`id_content_inline` =$id_content_inline LIMIT 1 ;";
        if (!$mysqli->query($req)) {
            $result['error'] = "SQL error : $req";
        } else {
            $result['success'] = true;
            $result['req'] = $req;
            $result['content'] = stripslashes($contenu_content_inline);
        }
    } else {
        $result['error'] = 'id_content_inline missing or val not set';
    }

    $mysqli->close();

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);

    if ($p_devmode) {
        $log .= " \n \n FIN";
        $fp = fopen('dev.txt', 'w');
        fwrite($fp, $log);
        fclose($fp);
    }
}
