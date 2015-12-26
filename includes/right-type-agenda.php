	<div id="right1">
		<div class="right-light">
			&nbsp; <!-- important -->
			<?php
			// PRESENTATION DE LA COMMISSION
			inclure('presentation-'.($current_commission?$current_commission:'general'),'right-light-in');

			// RECHERCHE
			include(INCLUDES.'recherche.php');
			?>
		</div>


		<div class="right-green">
			<div class="right-green-in">

				<?php
				// AGENDA sur fond vert
				include INCLUDES.'droite-agenda.php';
				?>

			</div>
		</div>

	</div>