<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<h1>Validation des sorties en tant que sortie officielle du CAF</h1>
			<?php
            if (!allowed('evt_legal_accept')) {
                echo '<p class="erreur">Droits insuffisants pour afficher cette page.</p>';
            } else {
                inclure($p1.'-main', 'vide');

                echo '<h2>'.$notif_validerunesortie_president.' Sortie'.($notif_validerunesortie_president > 1 ? 's' : '').' non validée'.($notif_validerunesortie_president > 1 ? 's' : '').', en attente de validation :</h2>';

                // liste :
                if (!$notif_validerunesortie_president) {
                    echo '<p class="info">Aucune sortie n\'est en attente de validation pour l\'instant.</p>';
                } else {
                    // ************
                    // ** AFFICHAGE, on recupere le design de l'agenda?>
					<!-- affichons tout ça dans le meme tableau que l'agenda -->
					<table id="agenda">
						<?php
                        for ($i = 0; $i < count($evtStandby); ++$i) {
                            $evt = $evtStandby[$i];

                            echo '<tr>'
                                    .'<td class="agenda-gauche">'.jour(date('N', $evt['tsp_evt']), 'short').' '.date('d', $evt['tsp_evt']).' '.mois(date('m', $evt['tsp_evt'])).'</td>'
                                    .'<td>'

                                        // Boutons
                                        .'<div class="evt-tools">'

                                            // apercu
                                            .'<a class="nice2" href="/sortie/'.html_utf8($evt['code_evt']).'-'.(int) $evt['id_evt'].'.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant validation" target="_blank">Aperçu / validation de la page</a> '

                                        .'</div>';

                            require __DIR__.'/../includes/agenda-evt-debut.php';
                            echo '</td>'
                                .'</tr>';
                        } ?>
					</table>

					<?php

                    // vars utiles pour affichage de chaque élément
                    $linkit = false;
                    $addClass = 'standby';
                    for ($i = 0; $i < count($evtStandby); ++$i) {
                        $evt = $evtStandby[$i];
                        if ($i % 2) {
                            $pair = true;
                        } else {
                            $pair = false;
                        }
                    }
                }

                // PAGES
                if ($nbrPages > 1) {
                    echo '<nav class="pageSelect"><hr />';
                    for ($i = 1; $i <= $nbrPages; ++$i) {
                        echo '<a href="'.$p1.'/'.$i.'.html" title="" class="'.($pagenum == $i ? 'up' : '').'">P'.$i.'</a> '.($i < $nbrPages ? '  ' : '');
                    }
                    echo '</nav>';
                }
            }
			?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__.'/../includes/right-type-agenda.php';
			?>


	<br style="clear:both" />
</div>