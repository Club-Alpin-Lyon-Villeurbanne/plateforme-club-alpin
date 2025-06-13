<!-- FOND : positionné comme un calque, en absolute, pour ajustement de l'image -->
<?php
$fond = comFd((int) ($comTab[$current_commission]['id_commission'] ?? 0));
?>
<div id="bigfond" <?php if (!empty($fond)) {
    echo 'style="background-image:url(' . $fond . ');"';
} ?>></div>
