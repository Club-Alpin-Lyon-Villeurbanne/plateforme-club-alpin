<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            if (!strlen($_GET['str'])) {
                echo '<h1>Recherche</h1>';
                inclure($p1, 'vide');
            } else {
                // TITRE
                echo '<h1>Votre recherche : &laquo;&nbsp;'.$safeStr.'&nbsp;&raquo;</h1>';

                // ERREURS
                if (isset($errTab) && count($errTab) > 0) {
                    echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                } else {
                    // RECHERCHE - ARTICLES
                    echo '<hr /><h2>Articles</h2>';

                    // compte
                    if (!count($articlesTab)) {
                        echo '<p class="alerte">Aucun article trouvé</p>';
                    } else {
                        echo '<p class="mini">'.$totalArticles.' article'.($totalArticles > 1 ? 's' : '').' trouvé'.($totalArticles > 1 ? 's' : '').'. '.($totalArticles != count($articlesTab) ? 'Voici les '.count($articlesTab).' plus récents :' : '').'</p>';
                    }

                    for ($i = 0; $i < count($articlesTab); ++$i) {
                        $article = $articlesTab[$i];
                        include INCLUDES.'article-lien.php';
                    }

                    // RECHERCHE - SORTIES
                    echo '<br /><hr /><h2>Sorties</h2>';

                    // compte
                    if (!count($evtTab)) {
                        echo '<p class="alerte">Aucune sortie trouvée</p>';
                    } else {
                        echo '<p class="mini">'.$totalEvt.' sortie'.($totalEvt > 1 ? 's' : '').' trouvée'.($totalEvt > 1 ? 's' : '').'. '.($totalEvt != count($evtTab) ? 'Voici les '.count($evtTab).' plus récentes :' : '').'</p>';
                    }

                    echo '<br /><table id="agenda">';
                    for ($i = 0; $i < count($evtTab); ++$i) {
                        $evt = $evtTab[$i];

                        echo '<tr>'
                                .'<td class="agenda-gauche">'.date('d/m/Y', $evt['tsp_evt']).'</td>'
                                .'<td>';
                        include INCLUDES.'agenda-evt-debut.php';
                        echo '</td>'
                            .'</tr>';
                    }
                    echo '</table>';
                }
            }
            // - Sorties

            ?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    include INCLUDES.'right-type-agenda.php';
    ?>

	<br style="clear:both" />
</div>
