<?php

use App\Helper\HtmlHelper;

if (allowed('user_read_limited')) {
    echo HtmlHelper::escape(ucfirst($tmpUser['firstname_user']) . ' ' . strtoupper($tmpUser['lastname_user']) . ' (' . $tmpUser['nickname_user'] . ')');
} else {
    echo HtmlHelper::escape($tmpUser['nickname_user']);
}
