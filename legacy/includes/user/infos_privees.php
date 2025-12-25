<?php

use App\Helper\HtmlHelper;

if (allowed('user_read_private')) {
    echo '<hr  />'
        . '<h3>Infos privées : </h3>'
        . '<ul class="nice-list">'
        . '<li>NUMÉRO DE LICENCE FFCAM : ' . HtmlHelper::escape($tmpUser['cafnum_user']) . '</a> </li>';
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
    $age = 0;
    if (!empty($tmpUser['birthdate'])) {
        $birthdate = new \DateTime($tmpUser['birthdate']);
        $age = $birthdate->diff(new \DateTime())->y;
    }
    echo '<li><a href="mailto:' . HtmlHelper::escape($tmpUser['email_user']) . '" title="Contact direct">' . HtmlHelper::escape($tmpUser['email_user']) . '</a> </li>'
        . '<li>TEL : ' . HtmlHelper::escape($tmpUser['tel_user']) . ' </li>'
        . '<li>TEL (secours) : ' . HtmlHelper::escape($tmpUser['tel2_user']) . ' </li>'
        . '<li>ÂGE : ' . ($age > 0 ? $age . ' ans' : '?') . ' </li>'
        . '<li class="wide">' . HtmlHelper::escape($tmpUser['adresse_user'] . ' ' . $tmpUser['cp_user'] . ' ' . $tmpUser['ville_user'] . ' ' . $tmpUser['pays_user']) . ' </li>'
    . '</ul>'
    . '<br style="clear:both" />'
    ;
}
