<?php

// default values if nothing provided

use App\Legacy\LegacyContainer;

if (isset($_GET['year']) && $_GET['year'] > 2000) {
    $year = (int) $_GET['year'];
} else {
    $year = (int) date('Y');
}

if (isset($_GET['month']) && $_GET['month'] > 0 && $_GET['month'] < 13) {
    $month = (int) $_GET['month'];
} else {
    $month = (int) date('m');
}

// nombre de jours dans ce mois (!! réutilisé dans la page !!)
$nDays = date('t', strtotime("$year-$month-10"));

// timestamp minimal et maximal
$start_tsp = mktime(0, 0, 0, $month, 1, $year); // premiere seconde du premier jour du mois
$end_tsp = mktime(23, 59, 59, $month, $nDays, $year); // derniere seconde du dernier jour

// echo 'start_tsp='.$start_tsp.'<hr />end_tsp='.$end_tsp.'<hr /><hr />';

// le tableau couvre tous les jours du mois
$agendaTab = [];
for ($i = 1; $i <= $nDays; ++$i) {
    $agendaTab[$i] = ['debut' => [], 'courant' => []];
}

// infos statistiques
$nEvts = 0; // nombre d'events démarrant de mois ci

$userId = null;
if (user()) {
    $userId = getUser()->getId();
}
$req = 'SELECT  e.id_evt, e.cancelled_evt, e.code_evt, e.tsp_evt, e.tsp_end_evt, e.tsp_crea_evt, e.commission_evt, e.titre_evt, e.massif_evt, e.place_evt, e.difficulte_evt, e.user_evt, e.is_draft ';
if (null !== $userId) {
    $req .= ', j.status_evt_join';
}
$req .= ', e.join_max_evt, e.ngens_max_evt, e.join_start_evt, e.id_groupe
            , c.title_commission, c.code_commission
    FROM caf_evt AS e
    INNER JOIN caf_commission as c ON (c.id_commission = e.commission_evt) ';
if (null !== $userId) {
    $req .= 'LEFT JOIN caf_evt_join AS j ON (e.id_evt = j.evt_evt_join AND user_evt_join = ' . $userId . ') ';
}
$req .= 'WHERE id_commission = e.commission_evt
    AND e.status_evt = 1 '
//  " AND cancelled_evt != 1 " // les sorties annulées y figurent ausssi
. ($p2 ? " AND code_commission = '" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2) . "' " : '')
// truc des dates :
. ' AND ( '
    // la fin de l'événement est comprise dans ce mois
    . " ( e.tsp_end_evt > $start_tsp AND e.tsp_end_evt < $end_tsp ) "
    // OU le début de l'événement est compris dans ce mois
    . " OR ( e.tsp_evt > $start_tsp AND e.tsp_evt < $end_tsp ) "
    // OU l'événement comprend l'intégralité du mois
    . " OR ( e.tsp_evt < $start_tsp AND e.tsp_end_evt > $end_tsp ) "
. ' ) '
. ' ORDER BY e.cancelled_evt ASC , e.tsp_evt ASC';

$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

// pour chaque event
while ($handleSql && $handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    $handle['groupe'] = get_groupe($handle['id_groupe']);

    // dates utiles pour ranger cet evenemtn dans le tableau
    $tmpStartD = date('d', $handle['tsp_evt']); // jour de cet evt de 1 à 28-30-31
    $tmpStartM = date('m', $handle['tsp_evt']); // mois de cet evt
    $tmpStartY = date('Y', $handle['tsp_evt']); // annee de cet evt
    $tmpEndD = date('d', $handle['tsp_end_evt']); // Jour de fin
    $tmpEndM = date('m', $handle['tsp_end_evt']); // Mois de fin
    $tmpEndY = date('Y', $handle['tsp_end_evt']); // annee de fin

    $handle['jourN'] = false; // compte des jours à afficher ?

    // s'il court sur plusieurs jours on initialise le compte des jours
    if ($tmpStartD . $tmpStartM != $tmpEndD . $tmpEndM) {
        $handle['jourN'] = 1;
    }

    // si cet événement débute ce mois
    if ($tmpStartM == $month) {
        // echo 'ADD '.$handle['id_evt'].' on '.$tmpStartD.'<hr />';
        // info statistique
        ++$nEvts;

        // compte places totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
        require __DIR__ . '/../includes/evt-temoin-reqs.php';

        // on l'ajoute au bon jour, colonne 'debut'
        $agendaTab[(int) $tmpStartD]['debut'][] = $handle;
    }
    // s'il court sur plusieurs jours (on inclut les evts qui commencent avant ce mois)
    if ($tmpStartD . $tmpStartM != $tmpEndD . $tmpEndM) {
        // on l'ajoute sur chaque jour ou il court sauf le premier, deja inqiqué colonne 'courant'
        $bool = true;
        // jour auquel commencer
        if ($tmpStartM != $month) {
            $i = 1;
        } // si l'evt a commencé avant le mois en cours, on commence à ajouter les lignes à 1 (premier jour)
        else {
            $i = $tmpStartD + 1;
        } // sinon, on commence à ajouter les lignes au jour du mois

        while ($bool) {
            // Nième jour de cet event :
            $tmpDay = mktime(23, 59, 59, $month, $i, $year); // jour ciblé ici
            $handle['jourN'] = ceil(($tmpDay - $handle['tsp_evt']) / 86400); // nombre de jours d'ecart

            // si ce jour dépasse le nombre de jours du mois, on s'arrête là
            if ($i > $nDays) {
                $bool = false;
            }
            // si ce jour est supérieur au jour de fin dans le bon mois, on s'arrête là
            if ($tmpEndM == $month && $i > $tmpEndD) {
                $bool = false;
            }

            if ($bool || 1 == $i) {
                // jour N si l'event est sur plusieur jours
                $agendaTab[$i]['courant'][] = $handle;
            }
            ++$i; // incrémenation d'un jour
        }
    }
}

$max_year = (int) date('Y') + 2;
$min_year = (int) date('Y') - 3;
?>

<!-- JS utiles a cette page -->
<script type="text/javascript" src="/js/faux-select.js"></script>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">

		<div style="padding:30px 20px 20px 20px">

			<!-- H1 : TITRE PRINCIPAL DE LA PAGE EN FONCTION DE LA COMM COURANTE -->
			<h1 class="agenda-h1">
				Agenda
				<?php
                if ($current_commission) {
                    echo ' : ' . $comTab[$current_commission]['title_commission'];
                }
?>
				<span style="color:silver">
					<?php
                    // echo sizeof($agendaTab)?' - '.sizeof($agendaTab).' sorties':'';
?>
				</span>
			</h1>

			<!-- sélection de la commission -->
			<div class="faux-select-wrapper" style="float:left;">
				<div class="faux-select faux-select-wide">
					<?php
                    // première ligne : tjrs toutes les comms
echo '<a href="/agenda.html?month=' . (int) $month . '&amp;year=' . (int) $year . '" title="" class="' . ($current_commission ? '' : 'up') . '">Toutes les sorties</a> ';
// choix de la commission
ksort($comTab);
foreach ($comTab as $code => $data) {
    echo '<a href="/agenda/' . html_utf8($code) . '.html?month=' . (int) $month . '&amp;year=' . (int) $year . '" title="" class="' . ($code == $current_commission ? 'up' : '') . '">' . html_utf8($data['title_commission']) . '</a> ';
}
?>
				</div>
			</div>

			<!-- sélection de la date -->
			<div class="faux-select-wrapper" style="float:left;">
				<div class="faux-select">
					<?php
$ampl = 6; // NOMBRE DE MOIS AVANT ET APRES LE MOIS COURANT à afficher

for ($i = $month - $ampl; $i <= $month + $ampl; ++$i) {
    // année et mois de cette ligne
    $tmpYear = $year;
    if ($i < 1) {
        $tmpMonth = 12 + $i;
        --$tmpYear;
    } elseif ($i > 12) {
        $tmpMonth = $i - 12;
        ++$tmpYear;
    } else {
        $tmpMonth = $i;
    }
    if ($tmpYear <= $max_year && $tmpYear >= $min_year) {
        echo '<a href="/agenda' . ($current_commission ? '/' . $current_commission : '') . '.html?month=' . $tmpMonth . '&amp;year=' . $tmpYear . '" class="' . ($month == $tmpMonth ? 'up' : '') . '">'
            . mois($tmpMonth) . ' ' . $tmpYear . '</a>';
    }
}
?>
				</div>
			</div>

			<!-- date en gris -->
			<p class="agenda-date"><?php echo mois($month) . ' ' . $year; ?></p>

			<br style="clear:both" />

			<?php
            // ajout de navigation
            $tmpMonth = $month - 1;
$tmpYear = $year;
if ($tmpMonth <= 0) {
    $tmpMonth = 12;
    --$tmpYear;
}
if ($tmpYear >= $min_year) {
    echo '<a style="float:left" href="/agenda' . ($p2 ? '/' . $p2 : '') . '.html?month=' . $tmpMonth . '&amp;year=' . $tmpYear . '" title="" class="fader2"><img src="/img/arrow-left.png" alt="&lt;" title="Mois précédent" style="height:30px" /></a>';
}

$tmpMonth = $month + 1;
$tmpYear = $year;
if ($tmpMonth > 12) {
    $tmpMonth = 1;
    ++$tmpYear;
}
if ($tmpYear <= $max_year) {
    echo '<a style="float:right" href="/agenda' . ($p2 ? '/' . $p2 : '') . '.html?month=' . $tmpMonth . '&amp;year=' . $tmpYear . '" title="" class="fader2"><img src="/img/arrow-right.png" alt="&gt;" title="Mois suivant" style="height:30px" /></a>';
}
?>

			<!-- Stat -->
			<p class="agenda-stat"><?php echo $nEvts . ' sortie' . ($nEvts > 2 ? 's' : '') . ' ce mois-ci :'; ?></p>

			<!-- Tableau des dates du mois courant -->
			<table id="agenda">
				<?php
    // optimisation, pour ne pas appeler 30 fois la fonction date :
    $weekTab = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
$iWeek = date('w', mktime(0, 0, 0, $month, 1, $year));

// boucle des jours
for ($i = 1; $i <= $nDays; ++$i) {
    // outil graphique pour afficher les délimiteurs
    $first = true;
    $bgwe = '';

    if (6 == $iWeek || 0 == $iWeek) {
        // we
        $bgwe = 'weekendday';
    }

    echo '<tr id="' . str_pad($i, 2, '0', \STR_PAD_LEFT) . '" class="' . (count($agendaTab[$i]['debut']) ? 'up' : 'off') . ' ' . $bgwe . '">' // ligne UP ou OFF si sortie démarre ou pas
            . '<td class="agenda-gauche ' . $bgwe . '">' . $weekTab[$iWeek] . ' ' . $i . ' ' . mois($month) . '</td>'
            . '<td>'
    ;

    // affichage des sorties commençantes
    for ($j = 0; $j < count($agendaTab[$i]['debut']); ++$j) {
        if (!$first) {
            echo '<hr />';
        }
        $first = false;
        $evt = $agendaTab[$i]['debut'][$j];
        require __DIR__ . '/../includes/agenda-evt-debut.php';
    }

    // affichage des sorties courantes
    for ($j = 0; $j < count($agendaTab[$i]['courant']); ++$j) {
        if (!$first) {
            echo '<hr />';
        }
        $first = false;
        $evt = $agendaTab[$i]['courant'][$j];
        require __DIR__ . '/../includes/agenda-evt-courant.php';
    }

    echo '</td>';

    ++$iWeek;
    if ($iWeek > 6) {
        $iWeek = 0;
    }
}
?>
			</table>
			<br style="clear:both" />

			<?php
            // ajout de navigation
            $tmpMonth = $month - 1;
$tmpYear = $year;
if ($tmpMonth <= 0) {
    $tmpMonth = 12;
    --$tmpYear;
}
if ($tmpYear >= $min_year) {
    echo '<a style="float:left" href="/agenda' . ($p2 ? '/' . $p2 : '') . '.html?month=' . $tmpMonth . '&amp;year=' . $tmpYear . '" title="" class="fader2"><img src="/img/arrow-left.png" alt="&lt;" title="Mois précédent" /></a>';
}

$tmpMonth = $month + 1;
$tmpYear = $year;
if ($tmpMonth > 12) {
    $tmpMonth = 1;
    ++$tmpYear;
}
if ($tmpYear <= $max_year) {
    echo '<a style="float:right" href="/agenda' . ($p2 ? '/' . $p2 : '') . '.html?month=' . $tmpMonth . '&amp;year=' . $tmpYear . '" title="" class="fader2"><img src="/img/arrow-right.png" alt="&gt;" title="Mois suivant" /></a>';
}
?>

			<!-- liens vers les flux RSS -->
			<br style="clear:both" />
			<br />
			<a href="/rss.xml?mode=sorties" title="Flux RSS de toutes les sorties du club" class="nice2">
				<img src="/img/base/rss.png" alt="RSS" title="" /> &nbsp;
				sorties du club
			</a>
			<?php
if ($current_commission) {
    echo '<a href="/rss.xml?mode=sorties-' . $current_commission . '" title="Flux RSS des sorties «' . $current_commission . '» uniquement" class="nice2">
						<img src="/img/base/rss.png" alt="RSS" title="" /> &nbsp;
						sorties «' . $current_commission . '»
					</a>';
}
?>
			<br style="clear:both" />
		</div>

	</div>

	<!-- partie droite -->
	<div id="right1">
		<div class="right-light">
			&nbsp; <!-- important -->
			<?php
// PRESENTATION DE LA COMMISSINO
inclure('presentation-' . ($current_commission ?: 'general'), 'right-light-in');

// SLIDER PARTENAIRES
require __DIR__ . '/../includes/droite-partenaires.php';

// RECHERCHE
require __DIR__ . '/../includes/recherche.php';
?>
		</div>


		<div class="right-green">
			<div class="right-green-in">

				<?php
    // ACTUS sur fond vert
    require __DIR__ . '/../includes/droite-actus.php';
?>

			</div>
		</div>

	</div>

	<br style="clear:both" />
</div>
