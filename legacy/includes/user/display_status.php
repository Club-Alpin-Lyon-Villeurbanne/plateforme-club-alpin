<h3>Gestion du club :</h3>
<ul class="nice-list">
    <?php
    foreach ($tmpUser['statuts']['club'] as $status) {
        echo '<li style="">' . $status . '</li>';
    } ?>
</ul>
<br style="clear:left;" />

<h3>Commissions :</h3>
<ul class="nice-list">
    <?php
    foreach ($tmpUser['statuts']['commissions'] as $status => $commissions) {
        echo '<li style="">' . $status;
        if (!empty($commissions) && is_array($commissions)) {
            echo ' : ' . $commissions[0];
        }
        echo '</li>';
    } ?>
</ul>
<br style="clear:left;" />
