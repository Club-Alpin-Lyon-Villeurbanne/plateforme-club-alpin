<?php

if (allowed('user_read_limited')) {
    echo html_utf8($tmpUser['civ_user'] . ' ' . $tmpUser['firstname_user'] . ' ' . $tmpUser['lastname_user'] . ' (' . $tmpUser['nickname_user'] . ')');
} else {
    echo html_utf8($tmpUser['nickname_user']);
}
if (allowed('user_read_private') && $tmpUser['doit_renouveler_user']) {
    echo '<div class="alerte">LICENCE EXPIRÉE</div>';
}
