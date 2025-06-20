<?php
// affichage complete d'une sortie.
// var necessaire : $article

use App\Legacy\LegacyContainer;

echo '<article id="fiche-article">';
if (!$article) {
    echo '<p class="erreur">Erreur : article non trouvé ou non autorisé</p>';
} else {
    // check image
    if ($article['media_upload_id']) {
        $img = LegacyContainer::get('legacy_twig')->getExtension('App\Twig\MediaExtension')->getLegacyThumbnail(['filename' => $article['filename']], 'wide_thumbnail');
        echo '<img src="' . $img . '" alt="image article" title="' . html_utf8($article['titre_article']) . '" class="wide-figure" />';
    } ?>
	<div class="titleimage">
		<h1>
			<?php
                echo html_utf8($article['titre_article']);
    ?>
		</h1>
        <div class="article-title-infos">
            <?php
            if (!empty($article['title_commission'])) {
                echo '<div class="article-title-commission">' . html_utf8($article['title_commission']) . '</div>';
            } elseif (!empty($article['evt']['title_commission'])) {
                echo '<div class="article-title-commission">' . html_utf8($article['evt']['title_commission']) . '</div>';
            }
    ?>
        </div>
		<div class="article-title-author">
			<?php
    echo 'Rédigé par ';
    echo userlink($article['auteur']['id_user'], $article['auteur']['nickname_user'], false, false, false, 'public', (int) $article['id_article']);
    echo ' le ' . date('d.m.Y', $article['tsp_article']);

    if ($totalComments > 1) {
        echo ', <a href="' . $_SERVER['REQUEST_URI'] . "#comments\">$totalComments commentaires</a>";
    } elseif ($totalComments > 0) {
        echo ', <a href="' . $_SERVER['REQUEST_URI'] . "#comments\">$totalComments commentaire</a>";
    } ?>
		</div>
	</div>

	<?php

    // contenu HTML
    echo '<div class="cont_article"><br />';

    if ('1' != $article['topubly_article']
        || '1' != $article['status_article']
        || (allowed('article_delete_notmine', 'commission:' . $article['code_commission'])
            || allowed('article_edit_notmine', 'commission:' . $article['code_commission'])
            || allowed('article_delete') && user() && $article['user_article'] == (string) getUser()->getId()
            || allowed('article_edit') && user() && $article['user_article'] == (string) getUser()->getId())
           && 1 == $article['status_article']) {
        echo '<div class="alerte noprint">';
    }

    // article trouvé mais normalement pas visible, c'est le cas d'un mode admin ou validateur
    if ('1' != $article['topubly_article'] && '1' != $article['status_article']) {
        echo '<b>Note :</b> Cet article est en cours de rédaction par <b>' . userlink($article['auteur']['id_user'], $article['auteur']['nickname_user']) . '</b>. La publication n\'a pas encore été demandée.<br />';
    } elseif ('1' != $article['status_article']) {
        echo '<b>Note :</b> Cet article n\'est pas publié sur le site. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br />';

        // Moderation
        if (allowed('article_validate', 'commission:' . $article['code_commission']) || allowed('article_validate_all')) {
            echo '
			<form action="' . $versCettePage . '" method="post" style="display:inline" class="loading">
				<input type="hidden" name="operation" value="article_validate" />
				<input type="hidden" name="status_article" value="1" />
				<input type="hidden" name="id_article" value="' . ((int) $article['id_article']) . '" />
				<input type="submit" value="Autoriser &amp; publier" class="nice2 green" title="Autorise instantanément la publication de la sortie" />
			</form>

			<input type="button" value="Refuser" class="nice2 red" onclick="$.fancybox($(this).next().html())" title="Ne pas autoriser la publication de cette sortie. Vous devrez ajouter un message au créateur de la sortie." />
			<div style="display:none" id="refuser-' . (int) $article['id_article'] . '">
				<form action="' . $versCettePage . '" method="post" class="loading">
					<input type="hidden" name="operation" value="article_validate" />
					<input type="hidden" name="status_article" value="2" />
					<input type="hidden" name="id_article" value="' . ((int) $article['id_article']) . '" />

					<p>Laissez un message à l\'auteur pour lui expliquer la raison du refus :</p>
					<input type="text" name="msg" class="type1" placeholder="ex : Décocher &laquo;A la Une&raquo;" />
					<input type="submit" value="Refuser la publication" class="nice2 red" />
					<input type="button" value="Annuler" class="nice2" onclick="$.fancybox.close()" />
				</form>
			</div><br />';
        }
    }

    if ((allowed('article_delete_notmine', 'commission:' . $article['code_commission'])
         || allowed('article_edit_notmine', 'commission:' . $article['code_commission'])
         || allowed('article_delete') && user() && $article['user_article'] == (string) getUser()->getId()
         || allowed('article_edit') && user() && $article['user_article'] == (string) getUser()->getId())
        && 1 == $article['status_article']) {
        echo '<b>Note :</b> Cet article est publié sur le site et visible par les adhérents !<br />';
    }

    // edition
    if (allowed('article_edit_notmine', 'commission:' . $article['code_commission'])
        || allowed('article_edit') && user() && $article['user_article'] == (string) getUser()->getId()) {
        echo '<a href="/article/' . (int) $article['id_article'] . '/edit" title="" class="nice2 orange">
			<img src="/img/base/pencil.png" alt="" title="" style="" />&nbsp;&nbsp;Modifier cet article
		</a>';
    }

    if ('1' != $article['status_article']
        && (allowed('article_delete_notmine', 'commission:' . $article['code_commission'])
         || allowed('article_delete') && user() && $article['user_article'] == (string) getUser()->getId())) {
        // Suppression
        echo '<a href="javascript:$.fancybox($(\'#supprimer-form-' . $article['id_article'] . '\').html());" title="" class="nice2 red">
				<img src="/img/base/x2.png" alt="" title="" style="" />&nbsp;&nbsp;Supprimer cet article
			</a>';
        echo '<div id="supprimer-form-' . (int) $article['id_article'] . '" style="display:none">
				<form action="' . $versCettePage . '" method="post" style="width:600px; text-align:left">
					<input type="hidden" name="operation" value="article_del" />
					<input type="hidden" name="id_article" value="' . $article['id_article'] . '" />
					<p>Voulez-vous vraiment supprimer définitivement cet article ? <br />Cette action est irréversible.</p>
					<input type="button" class="nice2" value="Annuler" onclick="$.fancybox.close();" />
					<input type="submit" class="nice2 red" value="Supprimer cet article" />
				</form>
			</div>';
    } elseif (allowed('article_validate_all')
              || allowed('article_validate', 'commission:' . $article['code_commission'])
              || allowed('article_edit') && user() && $article['user_article'] == (string) getUser()->getId()) {
        // article publié, on peut le depublier

        echo '<a href="javascript:$.fancybox($(\'#depublier-form-' . $article['id_article'] . '\').html());" title="" class="nice2 red" id="button-depublier">
				<img src="/img/base/pencil_delete.png" alt="" title="" style="" />&nbsp;&nbsp;Dépublier
			</a>
			<div id="depublier-form-' . $article['id_article'] . '" style="display:none">
				<form action="' . $versCettePage . '" method="post" style="width:600px; text-align:left">
					<input type="hidden" name="operation" value="article_depublier" />
					<input type="hidden" name="id_article" value="' . $article['id_article'] . '" />
					<p>Voulez-vous vraiment retirer cet article du site ? Il repassera en "Brouillon" et vous devrez à nouveau
					le faire publier par un responsable si vous désirez le publier à nouveau.</p>

					<input type="button" class="nice2" value="Annuler" onclick="$.fancybox.close();" />
					<input type="submit" class="nice2 orange" value="Dépublier mon article" />
				</form>
			</div>';
    }

    // remonter en tete
    if (allowed('article_validate_all') && (1 == $article['status_article'])) {
        echo '<a id="renew_date_article" href="javascript:do_renew_date_article();" class="nice2 green">
			<img src="/img/base/arrow_refresh_small.png" alt="" title="" style="" />&nbsp;&nbsp;Remonter en tête
		</a>';

        echo '<script>
				function do_renew_date_article () {

					$.ajax({
						type: "POST",
						dataType : "json",
						url: "/?ajx=operations",
						data: { operation: "renew_date_article", id_article: "' . $article['id_article'] . '" },
						success: function(jsonMsg){
							if(jsonMsg.success){
								$.fancybox(\'<p class="info">\'+jsonMsg.successmsg+\'</p>\');
							}
							else{
								$.fancybox(\'<p class="erreur">Erreur : <br />\'+(jsonMsg.error).join(\',<br />\')+\'</p>\');
							}
						}
					});
				};

				</script>';
    }

    // mêmes conditions que pour la balise ouvrante
    if ('1' != $article['topubly_article']
        || '1' != $article['status_article']
        || (allowed('article_delete_notmine', 'commission:' . $article['code_commission'])
            || allowed('article_edit_notmine', 'commission:' . $article['code_commission'])
            || allowed('article_delete') && user() && $article['user_article'] == (string) getUser()->getId()
            || allowed('article_edit') && user() && $article['user_article'] == (string) getUser()->getId())
           && 1 == $article['status_article']) {
        echo '</div><br />';
    }

    // compte rendu de sortie ?
    if (array_key_exists('evt', $article)) {
        $urlEvt = 'sortie/' . $article['evt']['code_evt'] . '-' . $article['evt']['id_evt'] . '.html';
        echo '<p class="italic">
             📋 Ceci est un compte-rendu de la sortie &laquo; <a href="' . $urlEvt . '" title="Voir la sortie liée à cet article">
                    ' . html_utf8($article['evt']['titre_evt']) . '
                </a> &raquo;
            </p>';
    }

    echo $article['cont_article'];
    echo '</div>';

    // BAS DE l'article
    if ('1' == $article['status_article']) {
        ?>
		<aside>

			<!-- partage -->
			<hr />
			<h2 class="share-h2">Pour partager cet article :</h2>
			<?php
            require __DIR__ . '/../includes/share.php'; ?>


			<!-- commentaires -->
			<hr id="comments" />
			<h2 class="comments-h2"><span><?php echo $totalComments; ?></span> Commentaires</h2>
			<br />
			<?php
            // AJOUTER UN COMM
            $parent_comment = $id_article;
        if (user()) {
            require __DIR__ . '/../includes/commenter-online.php';
        }

        // COMMS LIST
        foreach ($commentsTab as $comment) {
            require __DIR__ . '/../includes/comment.php';
        } ?>



		</aside>
		<?php
    }
}
echo '</article>';
?>


