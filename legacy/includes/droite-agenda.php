<?php

use App\Legacy\LegacyContainer;

$MAX_SORTIES_ACCUEIL = LegacyContainer::getParameter('legacy_env_MAX_SORTIES_ACCUEIL');

// nombre d'éléments à afficher
$limit = $MAX_SORTIES_ACCUEIL;

// LISTE DES PROCHAINES SORTIES PUBLIQUES (avec ou sans filtre commission)
$evtTab = [];
$evtTab2 = [];

    $req = 'SELECT  id_evt, cancelled_evt, code_evt, tsp_evt, tsp_crea_evt, titre_evt, massif_evt, cycle_master_evt, cycle_parent_evt
			, title_commission, code_commission
	FROM caf_evt, caf_commission
	WHERE id_commission = commission_evt
	AND status_evt = 1'
    // si une comm est sélectionnée, filtre
    .($current_commission ? " AND code_commission LIKE '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($current_commission)."' " : '')
    // seulement les sorties à venir
    .' AND tsp_evt > '.mktime(00, 00, 00, date('n'), date('j'), date('Y'))
    .' ORDER BY tsp_evt ASC
	LIMIT '.($limit + 10);

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $use = false;
        if ($id_dest = is_sortie_in_destination($handle['id_evt'])) {
            $status_dest = is_destination_status($id_dest, 'publie');
            $annule_dest = is_destination_status($id_dest, 'annule');
            if ($status_dest) {
                $use = true;
            }
            if ($annule_dest) {
                $handle['cancelled_evt'] = 1;
            }
        } else {
            $use = true;
        }
        if ($use) {
            $evtTab[] = $handle;
            --$limit;
        }
    }

// si les dates liées à cette commission sont trop peu nombreuses, on affiche toutes les autres comms
if ($current_commission) { // 2 minimum //11/04/2014&& sizeof($evtTab) < $limit-1
    $req = "SELECT  id_evt, cancelled_evt, code_evt, tsp_evt, tsp_crea_evt, titre_evt, massif_evt, cycle_master_evt, cycle_parent_evt
				, title_commission, code_commission
		FROM caf_evt, caf_commission
		WHERE id_commission = commission_evt
		AND status_evt = 1
		AND code_commission != '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($current_commission)."' "
        // seulement les sorties à venir
        .' AND tsp_evt > '.mktime(00, 00, 00, date('n'), date('j'), date('Y'))
        .' ORDER BY tsp_evt ASC
		LIMIT '.($limit + 10);

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $use = false;
        if ($id_dest = is_sortie_in_destination($handle['id_evt'])) {
            $status_dest = is_destination_status($id_dest, 'publie');
            $annule_dest = is_destination_status($id_dest, 'annule');
            if ($status_dest) {
                $use = true;
            }
            if ($annule_dest) {
                $handle['cancelled_evt'] = 1;
            }
        } else {
            $use = true;
        }
        if ($use) {
            $evtTab2[] = $handle;
            --$limit;
        }
    }
}

if ($current_commission) {
    echo '<h1 class="agenda-h1"><a href="agenda/'.$current_commission.'.html" title="Afficher l\'agenda complet pour cette commission">Agenda</a></h1>';
} else {
    echo '<h1 class="agenda-h1"><a href="agenda.html" title="Afficher l\'agenda complet">Agenda</a></h1>';
}
?>

<!-- Liste -->
<div id="evt-list">
	<?php
    // none
    if (!count($evtTab)) {
        echo '<p>Désolé, il n\'y a pas de sortie à venir pour cette commission...</p>';
    }
    // lsite
    for ($i = 0; $i < count($evtTab); ++$i) {
        $evt = $evtTab[$i];
        // si la commission est en cours, celle ci est précisée dans une var GET
        echo '<a href="sortie/'.html_utf8($evt['code_evt']).'-'.(int) ($evt['id_evt']).'.html?commission='.$evt['code_commission'].'" title="Voir la sortie">'
            .'<span style="color:#fff">'.jour(date('N', $evt['tsp_evt']), 'short').' '.date('d/m', $evt['tsp_evt']).'</span> | '
            .($evt['cancelled_evt'] ?
                '<span class="cancelled">Sortie annulée</span>'
                :
                '<span style="color:#4D4D4D">'.html_utf8($evt['title_commission']).'</span>'
            )
            .'<h2>'.html_utf8($evt['titre_evt']).'</h2>'
        .'</a>';
    }
    ?>
</div>

<!-- lien vers la page agenda -->
<?php
// si résultats il y a
if (count($evtTab)) {
    if ($current_commission) {
        echo '<a href="agenda/'.$current_commission.'.html" title="Afficher l\'agenda complet pour cette commission" class="lien-big">&gt; Voir toutes les sorties '.$comTab[$current_commission]['title_commission'].'</a>';
    } else {
        echo '<a href="agenda.html" title="Afficher l\'agenda complet" class="lien-big">&gt; Voir toutes les sorties</a>';
    }
}

// d'autres sorties pour compléter ?
if (count($evtTab2)) {
    ?>
	<!-- sous-titre -->
	<hr />
	<p><?php echo count($evtTab2); ?> sorties dans d'autres commissions :</p>
	<!-- Liste -->
	<div id="evt-list">
		<?php
        for ($i = 0; $i < count($evtTab2); ++$i) {
            $evt = $evtTab2[$i];
            echo '<a href="sortie/'.html_utf8($evt['code_evt']).'-'.(int) ($evt['id_evt']).'.html?commission='.$evt['code_commission'].'" title="Voir la sortie">'
                .'<span style="color:#fff">'.jour(date('N', $evt['tsp_evt']), 'short').' '.date('d/m', $evt['tsp_evt']).'</span> | '
                .($evt['cancelled_evt'] ?
                    '<span class="cancelled">Sortie annulée</span>'
                    :
                    '<span style="color:#4D4D4D">'.html_utf8($evt['title_commission']).'</span>'
                )
                .'<h2>'.html_utf8($evt['titre_evt']).'</h2>'
            .'</a>';
        } ?>
	</div>

	<!-- lien vers la page agenda -->
	<?php
    echo '<a href="agenda.html" title="Afficher l\'agenda complet" class="lien-big">&gt; Voir toutes les sorties</a>';
}
