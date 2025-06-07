<?php
if (user()) {
    ?>
	<div class="article-tools">

		<?php
        // statut de l'article
        if (0 == $article['topubly_article']) {
            echo "<p class='draft'>Brouillon : vous n'avez pas demandé à publier cet article pour le moment.</p>";
        } elseif (0 == $article['status_article']) {
            echo "<p class='alerte'>En attente : cet article n'a pas encore été publié par un responsable.</p>";
        } elseif (1 == $article['status_article']) {
            echo "<p class='info'>Publié : cet article est en ligne, " . $article['nb_vues_article'] . ' vues</p>';
        } elseif (2 == $article['status_article']) {
            echo "<p class='erreur'>Désactivé : cet article a été refusé par un responsable</p>";
        }

    // BOUTONS
    // publié ? voir
    if (1 == $article['status_article']) {
        ?>
			<a href="/article/<?php echo html_utf8($article['code_article'] . '-' . $article['id_article']); ?>.html" title="" class="nice2">
				Voir
			</a>
			<?php
    }

    // Sinon : apercu
    else {
        ?>
			<a href="/article/<?php echo html_utf8($article['code_article'] . '-' . $article['id_article']); ?>.html?forceshow=true" title="" class="nice2">
				Aperçu
			</a>
			<?php
    }

    // lier des co-rédacs
    /*
    ?>
    <a href="javascript:void(0)" title="" class="nice2">
        Co-rédacteurs
    </a>
    <?php
    */

    // on peut toujours modifier?>
		<a href="/article-edit/<?php echo (int) $article['id_article']; ?>.html" title="" class="nice2 orange">
			Modifier
		</a>
		<?php

        // si publié : dépublier
        if (1 == $article['status_article']) {
            ?>
			<a href="javascript:modal.show(document.getElementById('depublier-form-<?php echo $article['id_article']; ?>').innerHTML);" title="" class="nice2 red" id="button-depublier">
				Dépublier
			</a>
			<div id="depublier-form-<?php echo $article['id_article']; ?>" style="display:none">
				<form action="<?php echo $versCettePage; ?>" method="post" style="text-align:left">
					<input type="hidden" name="operation" value="article_depublier" />
					<input type="hidden" name="id_article" value="<?php echo $article['id_article']; ?>" />
					<p>Voulez-vous vraiment retirer cet article du site ? Il repassera en "Brouillon" et vous devrez à nouveau
					le faire publier par un responsable si vous désirez le publier à nouveau.</p>

					<input type="button" class="nice2" value="Annuler" onclick="modal.close();" />
					<input type="submit" class="nice2 orange" value="Dépublier mon article" />
				</form>
			</div>
			<?php
        }

    // si dépublié : supprimer
    if (1 != $article['status_article']) {
        ?>
			<a href="javascript:modal.show(document.getElementById('supprimer-form-<?php echo $article['id_article']; ?>').innerHTML);" title="" class="nice2 red">
				Supprimer
			</a>
			<div id="supprimer-form-<?php echo $article['id_article']; ?>" style="display:none">
				<form action="<?php echo $versCettePage; ?>" method="post" style="text-align:left">
					<input type="hidden" name="operation" value="article_del" />
					<input type="hidden" name="id_article" value="<?php echo $article['id_article']; ?>" />
					<p>Voulez-vous vraiment supprimer définitivement cet article ? <br />Cette action est irréversible.</p>

					<input type="button" class="nice2" value="Annuler" onclick="modal.close();" />
					<input type="submit" class="nice2 red" value="Supprimer mon article" />
				</form>
			</div>
			<?php
    } ?>

		<br style="clear:both" />
	</div>
	<?php
}
?>