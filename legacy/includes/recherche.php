<?php

use App\Helper\HtmlHelper;

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// suggestions
$tmpTab = ['Refuge du gouter', 'Enneigement', 'Ski de randonnée', 'Mont Blanc', 'Slackline'];

$link = LegacyContainer::get('legacy_router')->generate('search_result', [], UrlGeneratorInterface::ABSOLUTE_URL);
?>

<form action="<?php echo $link; ?>" id="recherche-form" class="right-light-in" method="post">
	<p class="big">Recherche :</p>
            <input type="hidden" name="csrf_token" value="<?php echo LegacyContainer::get('security.csrf.token_manager')->getToken('search')->getValue(); ?>" />
	<input type="text" class="textfield" name="str" value="<?php echo HtmlHelper::escape($_POST['str'] ?? ''); ?>" placeholder="ex: <?php echo $tmpTab[rand(0, count($tmpTab) - 1)]; ?>">
	<input type="submit" value="OK" class="submit" />
	<?php
    // filtre à la commission
    if ($current_commission) {
        echo '<br><input type="checkbox" name="commission" value="' . HtmlHelper::escape($current_commission) . '" id="search_filter" ' . (($_POST['commission'] ?? null) ? 'checked="checked"' : '') . '><label for="search_filter"> commission <span style="color:black">' . HtmlHelper::escape($comTab[$current_commission]['title_commission']) . '</span> uniquement</label>';
    }
?>
</form>
