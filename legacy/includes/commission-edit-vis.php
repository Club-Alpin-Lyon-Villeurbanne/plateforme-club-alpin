<?php
// Cette page sert à joindre manuellement un user à une sortie

if (user()) {
    // id de la comm
    $id_commission = (int) ($_GET['id_commission']);

    if (!allowed('comm_edit')) {
        echo '<p class="erreur">Vous n\'avez pas les droits requis pour afficher cette page</p>';
    } elseif (!$id_commission) {
        echo '<p class="erreur">ID de commission non spécifié</p>';
    } else {
        // recup comm
        $commission = false;
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $req = "SELECT * FROM caf_commission WHERE id_commission = $id_commission";
        $result = $mysqli->query($req);
        while ($row = $result->fetch_assoc()) {
            $commission = $row;
        }
        $mysqli->close;

        if (!$commission) {
            echo '<p class="erreur">Commission introuvable</p>';
        } else {
            // redirection si OK
            if (('commission_majvis' == $_POST['operation'] || 'commission_majvis' == $_POST['operation']) && (!isset($errTab) || 0 === count($errTab))) {
                ?>
				<p class="info">Mise à jour effectuée</p>
				<script type="text/javascript">
				top.window.location.href='gestion-des-commissions.html';
				top.window.location.reload();
				</script>
				<?php
            }

            // activer
            if (!$commission['vis_commission']) {
                ?>
				<h1>Activer cette commission</h1>
				<?php
                inclure('info-activer-commission'); ?>
				<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
					<input type="hidden" name="operation" value="commission_majvis" />
					<input type="hidden" name="vis_commission" value="1" />
					<input type="hidden" name="id_commission" value="<?php echo $id_commission; ?>" />

					<?php
                    // MESSAGES A LA SOUMISSION
                    if ('commission_majvis' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                    } ?>
					<br />
					<br />
					<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()" class="biglink">
						<span class="bleucaf">&gt;</span>
						ACTIVER&nbsp; &nbsp;  &laquo;&nbsp;<?php echo $commission['title_commission']; ?>&nbsp;&raquo;
					</a>

				</form>
				<?php
            }
            // desactiver
            else {
                ?>
				<h1>Désactiver cette commission</h1>
				<?php
                inclure('info-desactiver-commission'); ?>
				<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
					<input type="hidden" name="operation" value="commission_majvis" />
					<input type="hidden" name="vis_commission" value="0" />
					<input type="hidden" name="id_commission" value="<?php echo $id_commission; ?>" />

					<?php
                    // MESSAGES A LA SOUMISSION
                    if ('commission_majvis' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                    } ?>
					<br />
					<br />
					<a href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()" class="biglink">
						<span class="bleucaf">&gt;</span>
						DÉSACTIVER&nbsp; &nbsp;  &laquo;&nbsp;<?php echo $commission['title_commission']; ?>&nbsp;&raquo;
					</a>

				</form>
				<?php
            }
        }
    }
}
