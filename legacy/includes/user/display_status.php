<?php

if (!empty($tmpUser['statuts']['club'])) {
    echo '<h3>Responsabilité dans le club :</h3>';
    echo '<ul class="nice-list">';
    foreach ($tmpUser['statuts']['club'] as $status) {
        echo '<li>' . $status . '</li>';
    }
    echo '</ul>';
    echo '<br style="clear:left;" />';
}

if (!empty($tmpUser['statuts']['commissions'])) {
    echo '<h3>Responsabilité dans les commissions :</h3>';
    echo '<ul class="nice-list">';
    foreach ($tmpUser['statuts']['commissions'] as $status => $commissions) {
        echo '<li>' . $status;
        if (!empty($commissions) && is_array($commissions)) {
            echo ' : ' . $commissions[0];
        }
        echo '</li>';
    }
    echo '</ul>';
    echo '<br style="clear:left;" />';
}
