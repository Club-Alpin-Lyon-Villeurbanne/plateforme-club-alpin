<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<h1>Publication des sorties</h1>
			<?php
            if (!allowed('evt_validate')) {
                echo '<p class="erreur">Droits insuffisants pour afficher cette page.</p>';
            } else {
                inclure($p1.'-main', 'vide');

                // liste :
                if (!$notif_validerunesortie) {
                    echo '<p class="info">Aucune sortie n\'est en attente de publication pour l\'instant.</p>';
                } else {
                    echo '<h2>'.$notif_validerunesortie.' Sortie'.($notif_validerunesortie > 1 ? 's' : '').' proposée'.($notif_validerunesortie > 1 ? 's' : '').', en attente de publication :</h2>';
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
                                            .'<a class="nice2" href="sortie/'.html_utf8($evt['code_evt']).'-'.(int) ($evt['id_evt']).'.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication" target="_blank">Aperçu</a> ';

                            // Modération
                            echo '
											<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
												<input type="hidden" name="operation" value="evt_validate" />
												<input type="hidden" name="status_evt" value="1" />
												<input type="hidden" name="id_evt" value="'.((int) ($evt['id_evt'])).'" />
												<input type="submit" value="Autoriser &amp; publier" class="nice2 green" title="Autorise instantanément la publication de la sortie" />
											</form>

											<input type="button" value="Refuser" class="nice2 red" onclick="$.fancybox($(this).next().html())" title="Ne pas autoriser la publication de cette sortie. Vous devrez ajouter un message au créateur de la sortie." />
											<div style="display:none" id="refuser-'.(int) ($evt['id_evt']).'">
												<form action="'.$versCettePage.'" method="post" class="loading">
													<input type="hidden" name="operation" value="evt_validate" />
													<input type="hidden" name="status_evt" value="2" />
													<input type="hidden" name="id_evt" value="'.((int) ($evt['id_evt'])).'" />

													<p>Laissez un message à l\'auteur pour lui expliquer la raison du refus :</p>
													<input type="text" name="msg" class="type1" placeholder="ex: Mauvais point de RDV" />
													<input type="submit" value="Refuser la publication" class="nice2 red" />
													<input type="button" value="Annuler" class="nice2" onclick="$.fancybox.close()" />
												</form>
											</div>
											'
                                        .'</div>';

                            include __DIR__.'/../includes/agenda-evt-debut.php';
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
                        echo '<a href="'.($p_multilangue ? $lang.'/' : '').$p1.'/'.$i.'.html" title="" class="'.($pagenum == $i ? 'up' : '').'">P'.$i.'</a> '.($i < $nbrPages ? '  ' : '');
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
    include __DIR__.'/../includes/right-type-agenda.php';
    ?>


	<br style="clear:both" />
</div>