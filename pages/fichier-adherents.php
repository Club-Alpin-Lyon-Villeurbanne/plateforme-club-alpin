<!-- MAIN -->
<div id="main" role="main">
	<div style="padding:20px 10px;">
		<?php
        if (!allowed('user_updatefiles')) {
            echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour accéder à cette page</p>';
        } else {
            ?>

			<h1>Mise à jour des fichiers adhérent</h1>

			<p>
				<?php /* ?>Envoyez ci-dessous les fichiers <b>7300.txt</b> et <b>7380.txt</b> pour les mettre à jour. Le site "lit" régulièrement<?php */ ?>
				Envoyez ci-dessous le fichier <b>74800.txt</b> pour le mettre à jour. Le site "lit" régulièrement
				ce fichier pour créer les comptes des nouveaux inscrits et mettre à jour le statut des adhérents (par exemple
				s'ils doivent renouveler leur licence).
			</p>
			<p class="mini">
				Note : si l'upload ne fonctionne pas avec Internet Explorer, utilisez un meilleur navigateur comme Chrome, Firefox, Safari...
			</p>
			<br />


			<form action="<?php echo $versCettePage; ?>" method="post" enctype="multipart/form-data" class="loading">
				<input type="hidden" name="operation" value="fichier_adherents_maj" />

				<?php
                // MESSAGES A LA SOUMISSION
                if ('fichier_adherents_maj' == $_POST['operation'] && count($errTab)) {
                    echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                }
            if ('fichier_adherents_maj' == $_POST['operation'] && !count($errTab)) {
                echo '<p class="info">Mise à jour effectuée à '.date('H:i:s', $p_time).'.</p>';
            } ?>

				<input type="file" name="file[]" multiple />

				<input type="submit" class="nice2 " value="Envoyer" />

			</form>


			<hr />
			<?php
            //$fileTab=array('ftp/fichiers-proteges/7300.txt','ftp/fichiers-proteges/7380.txt');
            $fileTab = ['ftp/fichiers-proteges/74800.txt'];
            foreach ($fileTab as $file) {
                ?>
				<h2>Fichier <?php echo strtolower(substr(strrchr($file, '/'), 1)); ?></h2>
				<?php
                if (!file_exists($file) || !is_file($file)) {
                    echo '<p class="erreur">Fichier introuvable !</p>';
                } else {
                    // date
                    $tsp = filemtime($file);

                    // longueur
                    $linecount = 0;
                    $handle = fopen($file, 'r');
                    while (!feof($handle)) {
                        $line = fgets($handle);
                        ++$linecount;
                    }
                    fclose($handle); ?>
					<p>
						<img src="img/base/fichier.png" alt="" title="" style="float:left; padding:0 15px 0 0" /> Dernière modification le
						<?php
                        echo '<b>'.jour(date('N', $tsp)).' '.date('d', $tsp).' '.mois(date('m', $tsp)).' '.date('Y', $tsp).'</b> à '.date('H:i', $tsp); ?>
						<br />
						<b><?php echo $linecount; ?></b> adhérents (lignes).
					</p>
					<?php
                } ?>

				<br /><br />
				<?php
            }
        }
        ?>
		<br style="clear:both" />
	</div>
</div>
