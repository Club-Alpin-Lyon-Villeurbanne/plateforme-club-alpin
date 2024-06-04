<?php
echo '<a class="agenda-evt-debut" target="_top" href="/sortie/' . html_utf8($evt['code_evt']) . '-' . (int) $evt['id_evt'] . '.html?commission=' . $evt['code_commission'];
if (allowed('evt_validate') && isset($evt['status_evt']) && 1 != $evt['status_evt']) {
    echo '&forceshow=true';
}
echo '" title="">';
?>

	<!-- picto -->
	<div class="picto">
		<img src="<?php echo comPicto($evt['commission_evt'] ?? '', 'light'); ?>" alt="" title="" class="picto-light" />
		<img src="<?php echo comPicto($evt['commission_evt'] ?? '', 'dark'); ?>" alt="" title="" class="picto-dark" />
	</div>

	<div class="droite">

		<!-- temoin de validité des places libres. Ajouter class ok / full -->
        <span title="<?php echo $evt['temoin-title'] ?? ''; ?>" style="padding: 10px 10px 5px 5px;float:left;">
            <span class="temoin-places-dispos <?php if (isset($evt['temoin'])) {
                echo $evt['temoin'];
            } ?>"></span>
        </span>

		<!-- titre -->
		<h2>
			<?php
            if (isset($evt['cancelled_evt']) && $evt['cancelled_evt']) {
                echo ' <span style="padding:1px 3px; color:red; font-size:11px; font-family:Arial">SORTIE ANNULÉE - </span>';
            }
echo html_utf8($evt['titre_evt'] . (isset($evt['jourN']) && $evt['jourN'] ? ' [jour ' . $evt['jourN'] . ']' : ''));
if (isset($evt['groupe']) && is_array($evt['groupe'])) {
    echo ' <small>(' . html_utf8($evt['groupe']['nom']) . ')</small>';
}
if (isset($evt['cycle_master_evt']) && $evt['cycle_master_evt'] > 0) {
    // SORTIE DE DEBUT DE CYCLE
    echo ' <img src="/img/base/arrow_rotate_clockwise.png" width="16" height="16" alt="sortie de début de cycle" />';
} elseif ($evt['cycle_parent_evt'] > 0) {
    // SORTIE FAISANT PARTIE D'UN CYCLE
    echo ' <img src="/img/base/arrow_rotate_clockwise.png" width="16" height="16" alt="sortie faisant partie d\'un cycle" />';
}
?>
		</h2>

		<!-- infos -->
		<p>
			<?php
echo ''
    // commission
    . '<b>' . html_utf8($evt['title_commission']) . '</b>'
    // difficulté, ou pas
    . (isset($evt['difficulte_evt']) && $evt['difficulte_evt'] ? ' - <b>' . html_utf8($evt['difficulte_evt']) . '</b>' : '')
    // massif, ou pas
    . (isset($evt['massif_evt']) && $evt['massif_evt'] ? ' - <b>' . html_utf8($evt['massif_evt']) . '</b>' : '')
    // rôle de l'user dans cette sortie
    . (isset($evt['role_evt_join']) && $evt['role_evt_join'] ? ' - Votre rôle : <b>' . html_utf8($evt['role_evt_join']) . '</b>' : '')
;
?>
		</p>
	</div>
	<br style="clear:both" />

</a>
