<?php

$log = (isset($log) ? $log : '')."\n accès à ".date('H:i:s');
$result['success'] = false;

if (admin()) {
    if (!$code_content_html) {
        $code_content_html = stripslashes($_POST['code']);
    }

    $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

    $code_content_html = $mysqli->real_escape_string($code_content_html);
    $lang = $mysqli->real_escape_string($lang);
    $log .= "\n code_content_html :  ".$code_content_html;

    if ($code_content_html) {
        $contenu_content_inline = $mysqli->real_escape_string($contenu_content_inline);
        $req = 'SELECT `contenu_content_html` FROM  `'.$pbd."content_html` WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  '$lang' ORDER BY  `date_content_html` DESC  LIMIT 1";
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $result['success'] = true;
            // $result['req']=$req;
            $result['content'] = $handle['contenu_content_html'];
        }
    } else {
        $result['error'] = 'code_content_html missing';
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
