<?php
// affichage complete d'une sortie.
// var necessaire : $article

echo '<article id="fiche-article">';
if (!$article) {
    echo '<p class="erreur">Erreur : article non trouvé ou non autorisé</p>';
} else {
    // check image
    if (is_file('ftp/articles/'.(int) ($article['id_article']).'/wide-figure.jpg')) {
        $img = 'ftp/articles/'.(int) ($article['id_article']).'/wide-figure.jpg';
    } else {
        $img = 'ftp/articles/0/wide-figure.jpg';
    } ?>
	<div class="titleimage" style="background-image:url(<?php echo $img; ?>)">
		<h1>
			<?php
                echo html_utf8($article['titre_article']);
    if (allowed('article_validate_all')) {
        /*
        echo "\n";
        echo '<script >
            function do_une_article ( status_une ) {

                $.ajax({
                    type: "POST",
                    dataType : "json",
                    url: "index.php?ajx=operations",
                    data: { operation: "active_une_article", id_article: "'.$article['id_article'].'", new_status: status_une },
                    success: function(jsonMsg){
                        if(jsonMsg.success){
                            $.fancybox(\'<p class="info">\'+jsonMsg.successmsg+\'</p>\');
                            if (status_une == 1) {
                                $.(une_article_img).src=\'/img/star.png\';
                            } else {
                                $.(une_article_img).src=\'/img/star_off.png\';
                            }
                        }
                        else{
                            $.fancybox(\'<p class="erreur">Erreur : <br />\'+(jsonMsg.error).join(\',<br />\')+\'</p>\');
                        }
                    }
                });
            };

            </script>';

        if ($article['une_article']) {
            echo '&nbsp;&nbsp;&nbsp;<a id="une_article_off" href="javascript:do_une_article(\'0\');"><img id="une_article_img" src="/img/star.png" title="Supprimer de la une" width="16" height="16"></a>';
        } else {
            echo '&nbsp;&nbsp;&nbsp;<a id="une_article_off" href="javascript:do_une_article(\'1\');"><img id="une_article_img" src="/img/star_off.png" title="Ajouter à la une" width="16" height="16"></a>';
        }
            */
    } ?>
		</h1>
		<p class="date">
			<?php
            ?>
		</p>
		<p class="auteur">
			<?php

                echo 'Le '.date('d.m.Y', $article['tsp_article']);

    echo ', par ';
    echo userlink($article['auteur']['id_user'], $article['auteur']['nickname_user']);

    if ($totalComments > 1) {
        echo ', <a href="'.$_SERVER['REQUEST_URI']."#comments\">$totalComments commentaires</a>";
    } elseif ($totalComments > 0) {
        echo ', <a href="'.$_SERVER['REQUEST_URI']."#comments\">$totalComments commentaire</a>";
    }
    if (allowed('article_create')) {
        echo ', '.$article['nb_vues_article'].' vues';
    }
    // compte rendu de sortie ?
    if (-1 == $article['commission_article'] && $article['evt']) {
        $urlEvt = 'sortie/'.$article['evt']['code_evt'].'-'.$article['evt']['id_evt'].'.html'; ?> -
					Sortie liée :
					&laquo;
					<a href="<?php echo $urlEvt; ?>" title="Voir la sortie liée à cet article : &laquo; <?php echo html_utf8($article['evt']['titre_evt']); ?> &raquo;">
						<?php echo html_utf8($article['evt']['titre_evt']); ?></a>
					&raquo;
					<?php
    } ?>
		</p>
	</div>

	<?php

    // contenu HTML
    echo '<div class="cont_article"><br />';

    // article trouvé mais normalement pas visible, c'est le cas d'un mode admin ou validateur
    if ('1' != $article['topubly_article']) {
        echo '<div class="alerte noprint"><b>Note :</b> Cet article est en cours de rédaction par <b>'.userlink($article['auteur']['id_user'], $article['auteur']['nickname_user']).'</b>. La publication n\'a pas encore été demandée.<br />';
    } elseif ('1' != $article['status_article']) {
        echo '<div class="alerte noprint"><b>Note :</b> Cet article n\'est pas publié sur le site. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br />';

        // Moderation
        if (allowed('article_validate', 'commission:'.$article['commission_article']) || allowed('article_validate_all')) {
            echo '
			<form action="'.$versCettePage.'" method="post" style="display:inline" class="loading">
				<input type="hidden" name="operation" value="article_validate" />
				<input type="hidden" name="status_article" value="1" />
				<input type="hidden" name="id_article" value="'.((int) ($article['id_article'])).'" />
				<input type="submit" value="Autoriser &amp; publier" class="nice2 green" title="Autorise instantanément la publication de la sortie" />
			</form>

			<input type="button" value="Refuser" class="nice2 red" onclick="$.fancybox($(this).next().html())" title="Ne pas autoriser la publication de cette sortie. Vous devrez ajouter un message au créateur de la sortie." />
			<div style="display:none" id="refuser-'.(int) ($article['id_article']).'">
				<form action="'.$versCettePage.'" method="post" class="loading">
					<input type="hidden" name="operation" value="article_validate" />
					<input type="hidden" name="status_article" value="2" />
					<input type="hidden" name="id_article" value="'.((int) ($article['id_article'])).'" />

					<p>Laissez un message à l\'auteur pour lui expliquer la raison du refus :</p>
					<input type="text" name="msg" class="type1" placeholder="ex : Décocher &laquo;A la Une&raquo;" />
					<input type="submit" value="Refuser la publication" class="nice2 red" />
					<input type="button" value="Annuler" class="nice2" onclick="$.fancybox.close()" />
				</form>
			</div><br />';
        }
    }

    if ((allowed('article_delete_notmine') || allowed('article_delete', 'commission:'.$article['commission_article']) || allowed('article_edit_notmine') || allowed('article_edit', 'commission:'.$article['commission_article'])) && 1 == $article['status_article']) {
        echo '<div class="alerte noprint"><b>Note :</b> Cet article est publié sur le site et visible par les adhérents !<br />';
    }

    // edition
    if (allowed('article_edit_notmine') || allowed('article_edit', 'commission:'.$article['commission_article'])) {
        echo '<a href="article-edit/'.(int) ($article['id_article']).'.html" title="" class="nice2 orange">
			<img src="img/base/pencil.png" alt="" title="" style="" />&nbsp;&nbsp;Modifier cet article
		</a>';
    }

    if ('1' != $article['status_article'] && (allowed('article_delete_notmine') || allowed('article_delete', 'commission:'.$article['commission_article']))) {
        // Suppression
        echo '<a href="javascript:$.fancybox($(\'#supprimer-form-'.$article['id_article'].'\').html());" title="" class="nice2 red">
				<img src="img/base/x2.png" alt="" title="" style="" />&nbsp;&nbsp;Supprimer cet article
			</a>';
        echo '<div id="supprimer-form-'.(int) ($article['id_article']).'" style="display:none">
				<form action="'.$versCettePage.'" method="post" style="width:600px; text-align:left">
					<input type="hidden" name="operation" value="article_del" />
					<input type="hidden" name="id_article" value="'.$article['id_article'].'" />
					<p>Voulez-vous vraiment supprimer définitivement cet article ? <br />Cette action est irréversible.</p>
					<input type="button" class="nice2" value="Annuler" onclick="$.fancybox.close();" />
					<input type="submit" class="nice2 red" value="Supprimer cet article" />
				</form>
			</div>';
    } elseif (allowed('article_validate_all') || allowed('article_edit', 'commission:'.$article['commission_article'])) {
        // article publié, on peut le depublier

        echo '<a href="javascript:$.fancybox($(\'#depublier-form-'.$article['id_article'].'\').html());" title="" class="nice2 red" id="button-depublier">
				<img src="img/base/pencil_delete.png" alt="" title="" style="" />&nbsp;&nbsp;Dépublier
			</a>
			<div id="depublier-form-'.$article['id_article'].'" style="display:none">
				<form action="'.$versCettePage.'" method="post" style="width:600px; text-align:left">
					<input type="hidden" name="operation" value="article_depublier" />
					<input type="hidden" name="id_article" value="'.$article['id_article'].'" />
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

        //		echo '<input class="nice2 green" type="button" value="&nbsp;&nbsp;Remonter en tête" onclick="javascript:document.forms[\'renew-date-article-'.$article['id_article'].'\'].submit();" />';
        echo '<script>
				function do_renew_date_article () {

					$.ajax({
						type: "POST",
						dataType : "json",
						url: "index.php?ajx=operations",
						data: { operation: "renew_date_article", id_article: "'.$article['id_article'].'" },
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

    if ('1' != $article['topubly_article'] || allowed('article_validate', 'commission:'.$article['commission_article']) || allowed('article_validate_all') || allowed('article_delete_notmine') || allowed('article_delete', 'commission:'.$article['commission_article']) || allowed('article_edit_notmine') || allowed('article_edit', 'commission:'.$article['commission_article'])) {
        echo '</div><br />';
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
            include INCLUDES.'share.php'; ?>


			<!-- commentaires -->
			<hr id="comments" />
			<h2 class="comments-h2"><span><?php echo $totalComments; ?></span> Commentaires</h2>
			<br />
			<?php
            // AJOUTER UN COMM
            $parent_comment = $id_article;
        if (user()) {
            include INCLUDES.'commenter-online.php';
        }

        // COMMS LIST
        foreach ($commentsTab as $comment) {
            include INCLUDES.'comment.php';
        } ?>



		</aside>
		<?php
    }
}
echo '</article>';
?>


