<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<?php
            if (user()) {
                // droits
                if (!allowed('evt_delete')) {
                    echo '<br /><br /><br /><p class="erreur">Vous ne semblez pas disposer des droits de suppression.</p>';
                }
                // sortie non trouvée, pas de message d'erreur, équivalent à un 404
                if (!$evt && !$errPage) {
                    echo '<br /><br /><br /><p class="erreur">Hmmm... C\'est ennuyeux : nous n\'arrivons pas à trouver la sortie correspondant à cette URL.</p>';
                }
                // sortie non trouvée, avec message d'erreur, tentative d'accès mesquine ou sortié dévalidée
                if (!$evt && $errPage) {
                    echo '<div class="erreur">'.$errPage.'</div>';
                }
                // sortie trouvée, mais pas encore annulée

                // sortie trouvée, pas d'erreur, affichage normal :
                if ($evt && !$errPage) {
                    ?>
					<h1>Supprimer une sortie</h1>

					<?php
                    inclure($p1, 'vide'); ?>

					<form action="<?php echo $versCettePage; ?>" method="post" class="loading">
						<input type="hidden" name="operation" value="evt_del" />

						<?php
                        // TABLEAU
                        if (isset($_POST['operation']) && 'evt_del' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                        }
                    if (isset($_POST['operation']) && 'evt_del' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                        echo '<p class="info">Cette sortie a été annulée.</p>';
                    } ?>
						<br />

						<?php

                        if ($evt['cycle_master_evt'] > 0) {
                            echo "<b>Cette sortie est la première d'un cycle de plusieurs sorties. <b>Sa suppression entraînera la suppression de toutes les sorties du cycle.</b></b><br /><br />";
                        }

                    // si la sortie est publiée, on annonce que des e-mails vont être envoyés
                    if (1 == $evt['status_evt']) {
                        ?>

							<p class="alerte">Cette sortie est publiée sur le site. En la supprimant, vous créez une page introuvable.</p>
							<?php
                                if (false && $evt['cycle_master_evt']) {
                                    echo '<input type="checkbox" name="del_cycle_master_evt" value="1" checked /> <b>SORTIE DE DEBUT DE CYCLE</b>, supprimer toutes les sorties du cycle';
                                } ?>

							<a href="javascript:void(0)" title="Supprimer" class="nice2 red" onclick="$(this).parents('form').submit()">
								Supprimer définitivement la sortie ci-dessous
							</a>
							<?php
                    }
                    // sinon le message n'est pas necessaire
                    else {
                        ?>
							<p class="info">La sortie n'est pas publiée sur le site. Vous pouvez la supprimer sereinement.</p>
							<?php
                                if (false && $evt['cycle_master_evt']) {
                                    echo '<input type="checkbox" name="del_cycle_master_evt" value="1" checked /> <b>SORTIE DE DEBUT DE CYCLE</b>, supprimer toutes les sorties du cycle';
                                } ?>
							<a href="javascript:void(0)" title="Supprimer" class="nice2 red" onclick="$(this).parents('form').submit()">
								Supprimer définitivement la sortie ci-dessous
							</a>
							<?php
                    } ?>
					</form>
					<br />



					<br />
					<hr />
					<h2 style="text-align:center; background:white; padding:10px">APERÇU :</h2>
					<?php
                    // RESUME DE LA SORTIE
                    require __DIR__.'/../includes/evt-resume.php'; ?>



					<?php
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
