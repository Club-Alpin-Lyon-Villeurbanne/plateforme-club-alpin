<?php

use App\Legacy\LegacyContainer;

$evt = false;
$errPage = false; // message d'erreur spécifique à la page courante si besoin
$id_evt = (int) substr(strrchr($p2, '-'), 1);

// sélection complète, non conditionnelle par rapport au status
$req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt
            , cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt, matos_evt, need_benevoles_evt
            , lat_evt, long_evt
            , join_start_evt
            , ngens_max_evt, join_max_evt
            , nickname_user
            , title_commission, code_commission
    FROM caf_evt, caf_user, caf_commission
    WHERE id_evt=$id_evt
    AND id_user = user_evt
    AND commission_evt=id_commission
    LIMIT 1";
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // on a le droit de supprimer cette page ?
    if (allowed('evt_cancel', 'commission:' . $handle['code_commission'])) {
        // participants:
        $handle['joins'] = [];
        $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, nomade_user
                , role_evt_join
            FROM caf_evt_join, caf_user
            WHERE evt_evt_join =' . (int) $handle['id_evt'] . '
            AND user_evt_join = id_user
            LIMIT 300';
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $handle['joins'][] = $handle2;
        }

        // si la sortie est annulée, on recupère les details de "WHO" : qui l'a annulée
        if ('1' == $handle['cancelled_evt']) {
            $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user
                FROM caf_user
                WHERE id_user=' . (int) $handle['cancelled_who_evt'] . '
                LIMIT 300';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['cancelled_who_evt'] = $handle2;
            }
        }

        $evt = $handle;
    } else {
        $errPage = 'Accès non autorisé';
    }
}

?>


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
                    echo '<div class="erreur">' . $errPage . '</div>';
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
                            echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                        }
                    if (isset($_POST['operation']) && 'evt_del' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                        echo '<p class="info">Cette sortie a été annulée.</p>';
                    } ?>
						<br />

						<?php

                    // si la sortie est publiée, on annonce que des e-mails vont être envoyés
                    if (1 == $evt['status_evt']) {
                        ?>

							<p class="alerte">Cette sortie est publiée sur le site. En la supprimant, vous créez une page introuvable.</p>

							<a href="javascript:void(0)" title="Supprimer" class="nice2 red" onclick="$(this).parents('form').submit()">
								Supprimer définitivement la sortie ci-dessous
							</a>
							<?php
                    }
                    // sinon le message n'est pas necessaire
                    else {
                        ?>
							<p class="info">La sortie n'est pas publiée sur le site. Vous pouvez la supprimer sereinement.</p>
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
                    require __DIR__ . '/../includes/evt-resume.php'; ?>



					<?php
                }
            }
?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>

	<br style="clear:both" />
</div>
