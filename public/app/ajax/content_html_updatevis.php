<?php

$log .= "\n accès à ".date('H:i:s');
$result['success'] = false;

if (admin()) {
    $code_content_html = stripslashes($_POST['code_content_html']);
    $vis_content_html = (int) ($_POST['vis_content_html']);

    include SCRIPTS.'connect_mysqli.php';

    $code_content_html = $mysqli->real_escape_string($code_content_html);
    $lang = $mysqli->real_escape_string($lang); // défini dans les includes
    $log .= "\n code_content_html :  ".$code_content_html;

    if ($code_content_html) {
        // update VIS
        $req = 'UPDATE `'.$pbd."content_html` SET  `vis_content_html` =  '$vis_content_html' WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  '$lang' ORDER BY  `date_content_html` DESC  LIMIT 1";
        if ($mysqli->query($req)) {
            $result['success'] = true;
        }
        $log .= "\n req :  ".$req;
        // retour contenu si visible
        if ($vis_content_html) {
            $req = 'SELECT `contenu_content_html` FROM  `'.$pbd."content_html` WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  '$lang' ORDER BY  `date_content_html` DESC  LIMIT 1";
            $handleSql = $mysqli->query($req);
            while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
                $result['content'] = $handle['contenu_content_html'];
            }
        } else {
            $result['content'] = '<div class="blocdesactive"><img src="img/base/bullet_key.png" alt="" title="" /> Bloc de contenu désactivé</div>';
        }
        $log .= "\n retour : ".$result['content'];
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
