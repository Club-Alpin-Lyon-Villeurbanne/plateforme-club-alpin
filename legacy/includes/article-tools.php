<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Helper\HtmlHelper;

if (user()) {
    ?>
	<div class="article-tools">

		<?php
    // statut de l'article
    if (1 == $article['status_article']) {
        echo '<p class="info">Publié : cet article est en ligne</p>';
    } elseif (2 == $article['status_article']) {
        echo "<p class='erreur'>Désactivé : cet article a été refusé par les responsables de commission</p>";
    } elseif (0 == $article['topubly_article']) {
        echo "<p class='draft'>Brouillon (non publié)</p>";
    } elseif (0 == $article['status_article'] && 1 == $article['topubly_article']) {
        echo "<p class='alerte'>En attente : cet article n'a pas encore été publié par les responsables de commission</p>";
    }

    // BOUTONS
    // publié ? voir
    if (1 == $article['status_article']) {
        $article_link = LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $article['code_article'], 'id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL);
        ?>
			<a href="<?php echo $article_link; ?>" title="" class="nice2">
				Voir
			</a>
			<?php
    }

    // Sinon : apercu
    else {
        $article_link = LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $article['code_article'], 'id' => (int) $article['id_article'], 'forceshow' => 'true'], UrlGeneratorInterface::ABSOLUTE_URL);
        ?>
			<a href="<?php echo $article_link; ?>" title="" class="nice2">
				Aperçu
			</a>
			<?php
    }

    // on peut toujours modifier
    ?>
		<a href="<?php echo LegacyContainer::get('legacy_router')->generate('article_edit', ['id' => (int) $article['id_article']], UrlGeneratorInterface::ABSOLUTE_URL); ?>" title="" class="nice2 orange">
			Modifier
		</a>
		<?php

    // si publié : dépublier
    if (1 == $article['status_article']) {
        ?>
			<a href="javascript:$.fancybox($('#depublier-form-<?php echo $article['id_article']; ?>').html());" title="" class="nice2 red" id="button-depublier">
				Dépublier
			</a>
			<div id="depublier-form-<?php echo $article['id_article']; ?>" style="display:none">
				<form action="<?php echo $versCettePage; ?>" method="post" style="width:600px; text-align:left">
					<input type="hidden" name="operation" value="article_depublier" />
					<input type="hidden" name="id_article" value="<?php echo $article['id_article']; ?>" />
					<p>Voulez-vous vraiment retirer cet article du site ? Il repassera en "Brouillon" et vous devrez à nouveau
					le faire publier par un responsable si vous désirez le publier à nouveau.</p>

					<input type="button" class="nice2" value="Annuler" onclick="$.fancybox.close();" />
					<input type="submit" class="nice2 orange" value="Dépublier mon article" />
				</form>
			</div>
			<?php
    }

    // si dépublié : supprimer
    if (1 != $article['status_article']) {
        ?>
			<a href="javascript:$.fancybox($('#supprimer-form-<?php echo $article['id_article']; ?>').html());" title="" class="nice2 red">
				Supprimer
			</a>
			<div id="supprimer-form-<?php echo $article['id_article']; ?>" style="display:none">
				<form action="<?php echo $versCettePage; ?>" method="post" style="width:600px; text-align:left">
					<input type="hidden" name="operation" value="article_del" />
					<input type="hidden" name="id_article" value="<?php echo $article['id_article']; ?>" />
					<p>Voulez-vous vraiment supprimer définitivement cet article ? <br />Cette action est irréversible.</p>

					<input type="button" class="nice2" value="Annuler" onclick="$.fancybox.close();" />
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