<?php

if (allowed('user_read_private')) {
    echo '<hr  />'
        . '<h3>Infos privées : </h3>'
        . '<ul class="nice-list">'
        . '<li>NUMÉRO DE LICENCE : ' . html_utf8($tmpUser['cafnum_user']) . '</a> </li>';
    if (allowed('user_read_private') && $tmpUser['doit_renouveler_user']) {
        echo '<li class="red">LICENCE EXPIRÉE</li>';
    } elseif (allowed('user_read_private') && !empty($tmpUser['date_adhesion_user'])) {
        echo '<li>DATE D\'ADHÉSION : <span class="green">' . date('d/m/Y', $tmpUser['date_adhesion_user']) . '</span></li>';
    }
    echo '<li><a href="mailto:' . html_utf8($tmpUser['email_user']) . '" title="Contact direct">' . html_utf8($tmpUser['email_user']) . '</a> </li>'
        . '<li>TEL : ' . html_utf8($tmpUser['tel_user']) . ' </li>'
        . '<li>TEL (secours) : ' . html_utf8($tmpUser['tel2_user']) . ' </li>'
        . '<li>ÂGE : ' . ($tmpUser['birthday_user'] ? getYearsSinceDate($tmpUser['birthday_user']) . ' ans' : '?') . ' </li>'
        . '<li class="wide">' . html_utf8($tmpUser['adresse_user'] . ' ' . $tmpUser['cp_user'] . ' ' . $tmpUser['ville_user'] . ' ' . $tmpUser['pays_user']) . ' </li>'
    . '</ul>'
    . '<br style="clear:both" />'
    ;
}
