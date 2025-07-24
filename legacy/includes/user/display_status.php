<h3>Responsabilité dans le club :</h3>
<ul class="nice-list">
    <?php
    if (!empty($tmpUser['statuts']['club'])) {
        foreach ($tmpUser['statuts']['club'] as $status) {
            echo '<li>' . $status . '</li>';
        }
    } else {
        echo '<li>N/A</li>';
    } ?>
</ul>
<br style="clear:left;" />

<h3>Responsabilité dans les commissions :</h3>
<ul class="nice-list">
    <?php
    if (!empty($tmpUser['statuts']['commissions'])) {
        foreach ($tmpUser['statuts']['commissions'] as $status => $commissions) {
            echo '<li>' . $status;
            if (!empty($commissions) && is_array($commissions)) {
                echo ' : ' . $commissions[0];
            }
            echo '</li>';
        }
    } else {
        echo '<li>N/A</li>';
    } ?>
</ul>
<br style="clear:left;" />
