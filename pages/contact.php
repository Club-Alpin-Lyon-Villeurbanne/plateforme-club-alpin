<div id="main" role="main" class="contenu">
	<div style="float:left">
		<?php
        inclure($p1.($p2 ? '-'.$p2 : ''), 'type-gauche');
        ?>
		<br />
		<form action="<?php echo $versCettePage; ?>#main" method="post" id="contactform" class="type-gauche">
			<input type="hidden" name="operation" value="contact" />

			<?php
            // TABLEAU
            if ('contact' == $_POST['operation'] && count($errTab)) {
                echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
            }
            if ('contact' == $_POST['operation'] && !count($errTab)) {
                echo '<div class="info">Votre message a bien été envoyé. Nous vous répondrons dès que possible.</div>';
            } else {
                ?>

			<div style="float:left; clear:both;">
				Votre nom<br />
				<input type="text" name="nom" class="type3" value="<?php echo inputVal('nom'); ?>"  />
			</div>
			<div style="float:right;">
				Votre prénom<br />
				<input type="text" name="prenom" class="type3" value="<?php echo inputVal('prenom'); ?>"  />
			</div>

			<div style="float:left; clear:both;">
				Votre e-mail<br />
				<input type="text" name="email" class="type3" value="<?php echo inputVal('email'); ?>"  />
			</div>
			<div style="float:right;">
				Numéro de téléphone<br />
				<input type="text" name="tel" class="type3" value="<?php echo inputVal('tel'); ?>" />
			</div>

			<br style="clear:both" />
			Objet de votre demande<br />
			<input type="text" name="objet" class="type3" value="<?php echo inputVal('objet', $_GET['objet']); ?>" style="width:615px;" />

			<br />
			Votre message<br />
			<textarea name="message" class="type3" rows="5" style="width:615px;" ><?php echo inputVal('message'); ?></textarea>
			<br />

			<div class="submit-button">
				<a href="javascript:void(0)" title="" onclick="$(this).siblings('input[name=lock]').val('unlocked').parents('form').submit();" class="submit">OK</a>
				<input type="hidden" name="lock" value="locked" />
			</div>
			<?php
            }
            ?>

		</form>
	</div>

	<br style="clear:both" />
</div>