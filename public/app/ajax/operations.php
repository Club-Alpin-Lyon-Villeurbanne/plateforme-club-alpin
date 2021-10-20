<?php

// Envoi et redimensionnement des réas

$log .= "\n accès à ".date('H:i:s');

// if(admin()){

    $errTab = [];
    $result = ['success' => false, 'error' => false];

    /*
    foreach($_POST as $key=>$val)
        $log .= "\n key=$key et val= $val ";
    */

    // retourne les post vars
    foreach ($_POST as $key => $val) {
        $result[$key] = $val;
    }

    include SCRIPTS.'operations.php';

    if (count($errTab)) {
        $result['error'] = $errTab;
    } else {
        $result['success'] = 1;
        $result['successmsg'] = $successmsg ?: 'Opération effectuée avec succès !';
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);

    if ($p_devmode) {
        $log .= " \n \n FIN";
        $fp = fopen('dev.txt', 'w');
        fwrite($fp, $log);
        fclose($fp);
    }

// }
