<?php

if (allowed('user_read_private')) {
    echo '<hr  />'
        . '<h3>Infos privées : </h3>'
        . '<ul class="nice-list">'
        . '<li>NUMÉRO DE LICENCE FFCAM : ' . html_utf8($tmpUser['cafnum_user']) . '</a> </li>';
    if (allowed('user_read_private') && $tmpUser['doit_renouveler_user']) {
        echo '<li class="red">LICENCE EXPIRÉE</li>';
    } elseif (allowed('user_read_private')) {
        echo '<li>DATE D\'ADHÉSION : ';
        if (!empty($tmpUser['join_date'])) {
            echo '<span class="green">' . (new \DateTimeImmutable($tmpUser['join_date']))?->format('d/m/Y') . '</span>';
        } else {
            echo 'inconnue';
        }
        echo '</li>';
    }
    $birthdate = new \DateTime($tmpUser['birthdate']);
    $age = $birthdate->diff(new \DateTime())->y;
    echo '<li><a href="mailto:' . html_utf8($tmpUser['email_user']) . '" title="Contact direct">' . html_utf8($tmpUser['email_user']) . '</a> </li>'
        . '<li>TEL : ' . html_utf8($tmpUser['tel_user']) . ' </li>'
        . '<li>TEL (secours) : ' . html_utf8($tmpUser['tel2_user']) . ' </li>'
        . '<li>ÂGE : ' . ($age > 0 ? $age . ' ans' : '?') . ' </li>'
        . '<li class="wide">' . html_utf8($tmpUser['adresse_user'] . ' ' . $tmpUser['cp_user'] . ' ' . $tmpUser['ville_user'] . ' ' . $tmpUser['pays_user']) . ' </li>'
    . '</ul>'
    . '<br style="clear:both" />'
    ;
}
