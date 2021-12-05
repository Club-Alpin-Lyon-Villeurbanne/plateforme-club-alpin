<?php

global $kernel;

$log = (isset($log) ? $log : '')."\n accès à ".date('H:i:s');
$result = null;

if (admin()) {
    $id_content_inline = (int) ($_POST['id']);
    $log .= "\n id_content_inline :  ".$id_content_inline;
    $code_content_inline = $_POST['code_content_inline'];
    $contenu_content_inline = $_POST['contenu_content_inline'];
    $log .= "\n contenu_content_inline :  \n".$contenu_content_inline;
    $contenu_content_inline = html_entity_decode($contenu_content_inline, \ENT_QUOTES, 'UTF-8');
    $log .= "\n html_entity_decode :  \n".$contenu_content_inline;
    $groupe_content_inline = (int) ($_POST['groupe_content_inline']);
    $log .= "\n groupe_content_inline :  \n".$groupe_content_inline;
    $lang_content_inline = stripslashes(utf8_encode($_POST['lang_content_inline']));
    $log .= "\n lang_content_inline :  \n".$lang_content_inline;
    $linkedtopage_content_inline = stripslashes(utf8_encode($_POST['linkedtopage_content_inline']));
    $log .= "\n linkedtopage_content_inline :  \n".$linkedtopage_content_inline;

    if (isset($_POST['id_content_inline']) && isset($_POST['lang_content_inline']) && isset($_POST['contenu_content_inline'])) {
        $code_content_inline = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($code_content_inline);
        $contenu_content_inline = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($contenu_content_inline);
        $lang_content_inline = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($lang_content_inline);
        $linkedtopage_content_inline = $kernel->getContainer()->get('legacy_mysqli_handler')->escapeString($linkedtopage_content_inline);

        // entrée à créer
        if (!$id_content_inline) {
            $req = "INSERT INTO  `caf_content_inline` (`id_content_inline` ,`groupe_content_inline` ,`code_content_inline` ,`lang_content_inline` ,`contenu_content_inline` ,`date_content_inline` ,`linkedtopage_content_inline`)
														VALUES (NULL ,  '$groupe_content_inline',  '$code_content_inline',  '$lang_content_inline',  '$contenu_content_inline',  '".time()."',  '$linkedtopage_content_inline');";
        }
        // entrée existante
        else {
            $req = "UPDATE  `caf_content_inline` SET  `contenu_content_inline` =  '$contenu_content_inline', `date_content_inline` =  '".time()."'  WHERE  `caf_content_inline`.`id_content_inline` =$id_content_inline LIMIT 1 ;";
        }

        $log .= "\n SQL : ";
        if (!$kernel->getContainer()->get('legacy_mysqli_handler')->query($req)) {
            $log .= "ERREUR : $req";
        } else {
            $log .= "\n OK ";
            $result['success'] = true;
            $result['req'] = $req;
            $result['content'] = stripslashes($contenu_content_inline);
        }
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
