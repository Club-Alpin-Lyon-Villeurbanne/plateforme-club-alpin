<?php

if (allowed('user_read_limited')) {
    echo html_utf8(ucfirst($tmpUser['firstname_user']) . ' ' . strtoupper($tmpUser['lastname_user']) . ' (' . $tmpUser['nickname_user'] . ')');
} else {
    echo html_utf8($tmpUser['nickname_user']);
}
