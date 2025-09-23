<h3>Responsabilité sur le site :</h3>
<ul class="nice-list">
    <?php
    if (!empty($tmpUser['statuts']['club'])) {
        foreach ($tmpUser['statuts']['club'] as $statusInfos) {
            echo '<li>' . $statusInfos['title'];
            if (!empty($statusInfos['desc'])) {
                echo ' <img src="/img/base/info.png" title="' . $statusInfos['desc'] . '" />';
            }
            echo '</li>';
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
        foreach ($tmpUser['statuts']['commissions'] as $commission => $statuses) {
            echo '<li>' . $commission;
            if (!empty($statuses) && is_array($statuses)) {
                echo ' : ' . $statuses[0]['title'];
                if (!empty($statuses[0]['desc'])) {
                    echo ' <img src="/img/base/info.png" title="' . $statuses[0]['desc'] . '" />';
                }
            }
            echo '</li>';
        }
    } else {
        echo '<li>N/A</li>';
    } ?>
</ul>
<br style="clear:left;" />
