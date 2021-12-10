<?php

use App\Legacy\LegacyContainer;

$log = (isset($log) ? $log : '')."\n accès à ".date('H:i:s');
$result['success'] = false;

if (admin()) {
    if (!$code_content_html) {
        $code_content_html = stripslashes($_POST['code']);
    }

    $code_content_html = LegacyContainer::get('legacy_mysqli_handler')->escapeString($code_content_html);
    $lang = LegacyContainer::get('legacy_mysqli_handler')->escapeString($lang);
    $log .= "\n code_content_html :  ".$code_content_html;

    if ($code_content_html) {
        $contenu_content_inline = LegacyContainer::get('legacy_mysqli_handler')->escapeString($contenu_content_inline);
        $req = "SELECT `contenu_content_html` FROM  `caf_content_html` WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  '$lang' ORDER BY  `date_content_html` DESC  LIMIT 1";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $result['success'] = true;
            // $result['req']=$req;
            $result['content'] = $handle['contenu_content_html'];
        }
    } else {
        $result['error'] = 'code_content_html missing';
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
