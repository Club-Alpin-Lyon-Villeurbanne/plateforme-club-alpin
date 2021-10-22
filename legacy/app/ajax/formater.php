<?php

if (admin()) {
    $str = utf8_encode($_POST['str']);
    $type = (int) ($_POST['type']);
    $result = [];
    $result['content'] = htmlspecialchars(substr(formater($str, $type), 0, 45), \ENT_NOQUOTES);
    $result['success'] = true;

    // to pass data through iframe you will need to encode all html tags
    echo json_encode($result);
}
