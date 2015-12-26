<?php

	// STATUT LÉGAL : À PARTIR DE J-2 (timestamp défini dans params.php)
	// echo 'dev : '.date("d-m-y H:i", $p_tsp_max_pour_valid_legal_avant_evt);
	if($evt['tsp_evt'] < $p_tsp_max_pour_valid_legal_avant_evt && $evt['tsp_evt'] > $p_time){
		inclure('status-legal-'.intval($evt['status_legal_evt']), 'status-legal');
		echo '<br />';

		if(allowed('evt_legal_accept') && $evt['status_legal_evt']==0 && $evt['status_evt']==1){
			?>
			<div class="status-legal noprint">
				<h2>Validation de la sortie :</h2>
				<p>
					Pour valider cette sortie en tant que sortie officielle du CAF,
					ou refuser d'associer cette sortie au <?php echo $p_sitename; ?>,
					cliquez sur un des boutons ci-dessous.
				</p>
				<p>
					<b>Attention !</b> Cette opération est définitive !
				</p>

				<form action="<?php echo $versCettePage;?>" method="post" class="loading" style="display:inline">
					<input type="hidden" name="operation" value="evt_legal_update" />
					<input type="hidden" name="status_legal_evt" value="2" />
					<input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
					<input type="submit" class="nice2 red" value="Refuser la validation" />
				</form>
				<form action="<?php echo $versCettePage;?>" method="post" class="loading" style="display:inline">
					<input type="hidden" name="operation" value="evt_legal_update" />
					<input type="hidden" name="status_legal_evt" value="1" />
					<input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
					<input type="submit" class="nice2 green" value="Valider" />
				</form>

				<br />&nbsp;
			</div>
			<br />
			<?php
		}
	}