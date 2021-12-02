<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<h1>Publication des articles</h1>
			<?php
            if (!allowed('article_validate') && !allowed('article_validate_all')) {
                echo '<p class="erreur">Droits insuffisants pour afficher cette page.</p>';
            } else {
                inclure($p1.'-main', 'vide');

                if ($notif_validerunarticle > 0) {
                    echo '<br /><h2>'.$notif_validerunarticle.' article'.($notif_validerunarticle > 1 ? 's' : '').' proposé'.($notif_validerunarticle > 1 ? 's' : '').' en attente de publication :</h2>';
                }

                // liste articles en attente de publication :
                if (!$notif_validerunarticle) {
                    echo '<p class="info">Aucun article n\'est en attente de publication pour l\'instant.</p>';
                } else {
                    // ************
                    // ** AFFICHAGE, on recupere le design de l'agenda
                    for ($i = 0; $i < count($articleStandby); ++$i) {
                        $article = $articleStandby[$i];

                        // check image
                        if (is_file(__DIR__.'/../../public/ftp/articles/'.(int) ($article['id_article']).'/wide-figure.jpg')) {
                            $img = '/ftp/articles/'.(int) ($article['id_article']).'/wide-figure.jpg';
                        } else {
                            $img = '/ftp/articles/0/wide-figure.jpg';
                        }

                        // type d'article : lié à l'id de la commission en fait
                        if (0 == $article['commission_article']) {
                            $type = 'Actualité du club (toutes les commissions)';
                        } elseif (-1 == $article['commission_article']) {
                            $type = 'Compte rendu de sortie';
                        } else {
                            $type = 'Actualité « '.$article['title_commission'].' »';
                        }

                        // Aff
                        echo '<hr />'
                        // Boutons
                        .'<div class="article-tools-valid">'

                            // apercu
                            .'<a class="nice2" href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication" target="_blank">Aperçu</a> ';

                        // Moderation
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
							</div>';
                        echo '</div>'

                            .'<div style="width:100px; float:left; padding:6px 10px 0 0;"><a href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" target="_blank">'
                                // image liee
                                .'<img src="'.$img.'" alt="" title="" style="width:100%; " />'
                            .'</a></div>'
                            .'<div style="float:right; width:510px">'

                            // INFOS
                            .'<p style="padding:5px 5px; line-height:18px;">'
                                .'<b><a href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" target="_blank">'.html_utf8($article['titre_article']).'</a></b><br />'
                                .'<b>Type d\'article :</b> '.$type.'<br />'
                                .'<span class="mini">Par '.userlink($article['id_user'], $article['nickname_user']).'</span> - '
                                .'<span class="mini">Le '.jour(date('N', $article['tsp_article']), 'short').' '.date('d', $article['tsp_article']).' '.mois(date('m', $article['tsp_article'])).' à '.date('H:i', $article['tsp_article']).'<br />'
                                .($article['une_article'] ? '<span class="mini"><b><img src="/img/base/star.png" style="vertical-align:bottom; height:13px;" /> Article à la UNE</b> : cet article sera placé dans le slider de la page d\'accueil !</span>' : '')
                            .'</ul>'

                        .'</div>'
                        .'<br style="clear:both" />';
                    }
                }

                // liste articles cours de redaction :
                $notif_validerunarticle = count($articleStandbyRedac);
                if (0 == $notif_validerunarticle) {
                    echo '<p class="info">Aucun article n\'est en cours de rédaction pour l\'instant.</p>';
                } else {
                    echo '<br /><br /><h2>'.$notif_validerunarticle.' article'.($notif_validerunarticle > 1 ? 's' : '').' en cours de rédaction dont la publication n\'a pas été demandée :</h2>';

                    // ************
                    // ** AFFICHAGE, on recupere le design de l'agenda
                    for ($i = 0; $i < count($articleStandbyRedac); ++$i) {
                        $article = $articleStandbyRedac[$i];

                        // check image
                        if (is_file(__DIR__.'/../../public/ftp/articles/'.(int) ($article['id_article']).'/wide-figure.jpg')) {
                            $img = '/ftp/articles/'.(int) ($article['id_article']).'/wide-figure.jpg';
                        } else {
                            $img = '/ftp/articles/0/wide-figure.jpg';
                        }

                        // type d'article : lié à l'id de la commission en fait
                        if (0 == $article['commission_article']) {
                            $type = 'Actualité du club (toutes les commissions)';
                        } elseif (-1 == $article['commission_article']) {
                            $type = 'Compte rendu de sortie';
                        } else {
                            $type = 'Actualité « '.$article['title_commission'].' »';
                        }

                        // Aff
                        echo '<hr />'
                        // Boutons
                        .'<div class="article-tools-valid">'

                            // apercu
                            .'<a class="nice2" href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication" target="_blank">Aperçu</a> ';

                        // edition
                        if (allowed('article_edit_notmine') || allowed('article_edit', 'commission:'.$article['commission_article'])) {
                            echo '<a href="article-edit/'.(int) ($article['id_article']).'.html" title="" class="nice2 orange">
									Modifier
								</a>';
                        }

                        // Suppression
                        if (allowed('article_delete_notmine') || allowed('article_delete', 'commission:'.$article['commission_article'])) {
                            echo '<a href="javascript:$.fancybox($(\'#supprimer-form-'.$article['id_article'].'\').html());" title="" class="nice2 red">
										Supprimer
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
                        }

                        echo '</div>';

                        echo '<div style="width:100px; float:left; padding:6px 10px 0 0;"><a href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" target="_blank">'
                                // image liee
                                .'<img src="'.$img.'" alt="" title="" style="width:100%; " />'
                            .'</a></div>'
                            .'<div style="float:right; width:510px">'

                            // INFOS
                            .'<p style="padding:5px 5px; line-height:18px;">'
                                .'<b><a href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" target="_blank">'.html_utf8($article['titre_article']).'</a></b><br />'
                                .'<b>Type d\'article :</b> '.$type.'<br />'
                                .'<span class="mini">Par '.userlink($article['id_user'], $article['nickname_user']).'</span> - '
                                .'<span class="mini">Le '.jour(date('N', $article['tsp_article']), 'short').' '.date('d', $article['tsp_article']).' '.mois(date('m', $article['tsp_article'])).' à '.date('H:i', $article['tsp_article']).'<br />'
                                .($article['une_article'] ? '<span class="mini"><b><img src="/img/base/star.png" style="vertical-align:bottom; height:13px;" /> Article à la UNE</b> : cet article sera placé dans le slider de la page d\'accueil !</span>' : '')
                            .'</ul>'

                        .'</div>'
                        .'<br style="clear:both" />';
                    }
                }
                // PAGES
                if ($nbrPages > 1) {
                    echo '<nav class="pageSelect"><hr />';
                    for ($i = 1; $i <= $nbrPages; ++$i) {
                        echo '<a href="'.$p1.'/'.$i.'.html" title="" class="'.($pagenum == $i ? 'up' : '').'">P'.$i.'</a> '.($i < $nbrPages ? '  ' : '');
                    }
                    echo '</nav>';
                }
            }
            ?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    include __DIR__.'/../includes/right-type-agenda.php';
    ?>


	<br style="clear:both" />
</div>