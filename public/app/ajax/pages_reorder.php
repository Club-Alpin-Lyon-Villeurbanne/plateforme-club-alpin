<?php

$log .= "\n accès à ".date('H:i:s');

if (admin()) {
    include SCRIPTS.'connect_mysqli.php';

    $i = 1;
    foreach ($_GET['id'] as $id_page) {
        $log .= "\n GET id_page = $id_page";
        $id_page = (int) $id_page;
        if ($id_page) {
            $req = 'UPDATE `'.$pbd."pdt` SET  `ordre_pdt` =  '".$ordre_pdt."' WHERE  `".$pbd.'pdt`.`id_pdt` ='.$id_pdt.' LIMIT 1 ;';
            $log .= "\n REQ : $req";
            $mysqli->query($req);
            --$ordre_pdt;
        }
    }
    $mysqli->close();

    if ($p_devmode) {
        $log .= " \n \n FIN";
        $fp = fopen('dev.txt', 'w');
        fwrite($fp, $log);
        fclose($fp);
    }
}
