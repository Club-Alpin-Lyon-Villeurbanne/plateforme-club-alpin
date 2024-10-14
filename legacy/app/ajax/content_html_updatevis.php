<?php

use App\Legacy\LegacyContainer;

$log = (isset($log) ? $log : '') . "\n accès à " . date('H:i:s');
$result['success'] = false;

if ($securityHelpers->isAdmin()) {
    $code_content_html = stripslashes($_POST['code_content_html']);
    $vis_content_html = (int) $_POST['vis_content_html'];

    $code_content_html = LegacyContainer::get('legacy_mysqli_handler')->escapeString($code_content_html);
    $log .= "\n code_content_html :  " . $code_content_html;

    if ($code_content_html) {
        // update VIS
        $req = "UPDATE `caf_content_html` SET  `vis_content_html` =  '$vis_content_html' WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  'fr' ORDER BY  `date_content_html` DESC  LIMIT 1";
        if (LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $result['success'] = true;
        }
        $log .= "\n req :  " . $req;
        // retour contenu si visible
        if ($vis_content_html) {
            $req = "SELECT `contenu_content_html` FROM  `caf_content_html` WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  'fr' ORDER BY  `date_content_html` DESC  LIMIT 1";
            $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
                $result['content'] = $handle['contenu_content_html'];
            }
        } else {
            $result['content'] = '<div class="blocdesactive"><img src="/img/base/bullet_key.png" alt="" title="" /> Bloc de contenu désactivé</div>';
        }
        $log .= "\n retour : " . $result['content'];
    } else {
        $result['error'] = 'code_content_html missing';
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
