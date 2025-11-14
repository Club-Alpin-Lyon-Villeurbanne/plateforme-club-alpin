<?php
// suggestions
$tmpTab = ['Refuge du gouter', 'Enneigement', 'Ski de randonnée', 'Mont Blanc', 'Slackline'];
?>

<form action="recherche.html" id="recherche-form" class="right-light-in" method="get">
	<p class="big">Recherche :</p>
	<input type="text" class="textfield" name="str" value="<?php echo html_utf8(stripslashes($_GET['str'] ?? '')); ?>" placeholder="ex: <?php echo $tmpTab[rand(0, count($tmpTab) - 1)]; ?>">
	<input type="submit" value="OK" class="submit" />
	<?php
    // filtre à la commission
    if ($current_commission) {
        echo '<br><input type="checkbox" checked="checked" name="commission" value="' . html_utf8($current_commission) . '" id="search_filter" ' . (($_GET['filter'] ?? null) ? 'checked="checked"' : '') . '><label for="search_filter"> commission <span style="color:black">' . $comTab[$current_commission]['title_commission'] . '</span> uniquement</label>';
    }
?>
</form>
