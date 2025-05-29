<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$log = (isset($log) ? $log : '') . "\n accès à " . date('H:i:s');
$result['success'] = false;

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    if (!isset($code_content_html)) {
        $code_content_html = stripslashes($_POST['code']);
    }

    $log .= "\n code_content_html :  " . $code_content_html;

    if ($code_content_html) {
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT `contenu_content_html` FROM  `caf_content_html` WHERE  `code_content_html` LIKE  ? AND  `lang_content_html` LIKE  'fr' ORDER BY  `date_content_html` DESC  LIMIT 1");
        $stmt->bind_param("s", $code_content_html);
        $stmt->execute();
        $handleSql = $stmt->get_result();
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $result['success'] = true;
            // $result['req']=$req;
            $result['content'] = $handle['contenu_content_html'];
        }
        $stmt->close();
    } else {
        $result['error'] = 'code_content_html missing';
    }

    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), \ENT_NOQUOTES);
}
