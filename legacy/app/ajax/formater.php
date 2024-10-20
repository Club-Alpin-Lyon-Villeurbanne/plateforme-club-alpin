<?php

use App\Security\SecurityConstants;

if (isGranted(SecurityConstants::ROLE_ADMIN)) {
    $str = mb_convert_encoding($_POST['str'], 'UTF-8');
    $type = (int) $_POST['type'];
    $result = [];
    $result['content'] = htmlspecialchars(substr(formater($str, $type), 0, 45), \ENT_NOQUOTES);
    $result['success'] = true;

    // to pass data through iframe you will need to encode all html tags
    echo json_encode($result);
}
