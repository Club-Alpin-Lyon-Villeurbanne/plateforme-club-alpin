<?php
$max_year = (int) (date('Y')) + 2;
$min_year = (int) (date('Y')) - 3;
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
                    echo ' : '.$comTab[$current_commission]['title_commission'];
                }
                ?>
				<span style="font-size:12px; color:silver">
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
                    echo '<a href="agenda.html?month='.(int) $month.'&amp;year='.(int) $year.'" title="" class="'.($current_commission ? '' : 'up').'">Toutes les sorties</a> ';
                    // choix de la commission
                    ksort($comTab);
                    foreach ($comTab as $code => $data) {
                        echo '<a href="agenda/'.html_utf8($code).'.html?month='.(int) $month.'&amp;year='.(int) $year.'" title="" class="'.($code == $current_commission ? 'up' : '').'">'.html_utf8($data['title_commission']).'</a> ';
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
                            echo '<a href="agenda'.($current_commission ? '/'.$current_commission : '').'.html?month='.$tmpMonth.'&amp;year='.$tmpYear.'" class="'.($month == $tmpMonth ? 'up' : '').'">'
                                .mois($tmpMonth).' '.$tmpYear.'</a>';
                        }
                    }
                    ?>
				</div>
			</div>

			<!-- date en gris -->
			<p class="agenda-date"><?php echo mois($month).' '.$year; ?></p>

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
                echo '<a style="float:left" href="agenda'.($p2 ? '/'.$p2 : '').'.html?month='.$tmpMonth.'&amp;year='.$tmpYear.'" title="" class="fader2"><img src="img/arrow-left.png" alt="&lt;" title="Mois précédent" style="height:30px" /></a>';
            }

            $tmpMonth = $month + 1;
            $tmpYear = $year;
            if ($tmpMonth > 12) {
                $tmpMonth = 1;
                ++$tmpYear;
            }
            if ($tmpYear <= $max_year) {
                echo '<a style="float:right" href="agenda'.($p2 ? '/'.$p2 : '').'.html?month='.$tmpMonth.'&amp;year='.$tmpYear.'" title="" class="fader2"><img src="img/arrow-right.png" alt="&gt;" title="Mois suivant" style="height:30px" /></a>';
            }
            ?>

			<!-- Stat -->
			<p class="agenda-stat"><?php echo $nEvts.' sortie'.($nEvts > 2 ? 's' : '').' ce mois-ci :'; ?></p>

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

                    echo '<tr class="'.(count($agendaTab[$i]['debut']) ? 'up' : 'off').' '.$bgwe.'">' // ligne UP ou OFF si sortie démarre ou pas
                            .'<td class="agenda-gauche '.$bgwe.'">'.$weekTab[$iWeek].' '.$i.' '.mois($month).'</td>'
                            .'<td>'
                            ;

                    // affichage des sorties commençantes
                    for ($j = 0; $j < count($agendaTab[$i]['debut']); ++$j) {
                        if (!$first) {
                            echo '<hr />';
                        }
                        $first = false;
                        $evt = $agendaTab[$i]['debut'][$j];
                        include __DIR__.'/../includes/agenda-evt-debut.php';
                    }

                    // affichage des sorties courantes
                    for ($j = 0; $j < count($agendaTab[$i]['courant']); ++$j) {
                        if (!$first) {
                            echo '<hr />';
                        }
                        $first = false;
                        $evt = $agendaTab[$i]['courant'][$j];
                        include __DIR__.'/../includes/agenda-evt-courant.php';
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
                echo '<a style="float:left" href="agenda'.($p2 ? '/'.$p2 : '').'.html?month='.$tmpMonth.'&amp;year='.$tmpYear.'" title="" class="fader2"><img src="img/arrow-left.png" alt="&lt;" title="Mois précédent" /></a>';
            }

            $tmpMonth = $month + 1;
            $tmpYear = $year;
            if ($tmpMonth > 12) {
                $tmpMonth = 1;
                ++$tmpYear;
            }
            if ($tmpYear <= $max_year) {
                echo '<a style="float:right" href="agenda'.($p2 ? '/'.$p2 : '').'.html?month='.$tmpMonth.'&amp;year='.$tmpYear.'" title="" class="fader2"><img src="img/arrow-right.png" alt="&gt;" title="Mois suivant" /></a>';
            }
            ?>

			<!-- liens vers les flux RSS -->
			<br style="clear:both" />
			<br />
			<a href="rss.xml?mode=sorties" title="Flux RSS de toutes les sorties du club" class="nice2">
				<img src="img/base/rss.png" alt="RSS" title="" /> &nbsp;
				sorties du club
			</a>
			<?php
            if ($current_commission) {
                echo '<a href="rss.xml?mode=sorties-'.$current_commission.'" title="Flux RSS des sorties «'.$current_commission.'» uniquement" class="nice2">
						<img src="img/base/rss.png" alt="RSS" title="" /> &nbsp;
						sorties «'.$current_commission.'»
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
            inclure('presentation-'.($current_commission ?: 'general'), 'right-light-in');

            // SLIDER PARTENAIRES
            include __DIR__.'/../includes/droite-partenaires.php';

            // RECHERCHE
            include __DIR__.'/../includes/recherche.php';
            ?>
		</div>


		<div class="right-green">
			<div class="right-green-in">

				<?php
                // ACTUS sur fond vert
                include __DIR__.'/../includes/droite-actus.php';
                ?>

			</div>
		</div>

	</div>

	<br style="clear:both" />
</div>
